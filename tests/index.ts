import fs from "node:fs";
import path from "node:path";
import dotenv from "dotenv"
import {OpenIdClient} from "./src/OpenIdClient";
import {HttpsServer} from "./src/HttpsServer";
import {HttpsClient} from "./src/HttpsClient";
import crypto from "crypto";
import { parse as parseHtml } from 'node-html-parser';
import {AxiosResponse} from "axios";

dotenv.config({ path: ".env" });
if (fs.existsSync(".env.local")) {
    dotenv.config({ path: ".env.local", override: true });
}

let httpsServer: HttpsServer;

async function run() {
    const env = process.env;
    if (!env.ISSUER_URL || !env.CLIENT_ID || !env.CLIENT_SECRET || !env.TLS_CA_CERT || !env.TLS_CERT || !env.TLS_KEY || !env.APP_BASE_URL || !env.WORDPRESS_USER || !env.WORDPRESS_PASS) {
        console.error("Some or all required environment variables were not defined. Set them in the .env file.");
        process.exit(1);
    }

    const caCert = fs.readFileSync(path.resolve(env.TLS_CA_CERT));

    const openIdClient = new OpenIdClient({
        issuerUrl: env.ISSUER_URL,
        clientId: env.CLIENT_ID,
        clientSecret: env.CLIENT_SECRET,
        redirectUri: env.APP_BASE_URL,
        caCert,
    });

    const httpsClient = new HttpsClient({
        caCert,
    })

    httpsServer = new HttpsServer({
        baseUrl: new URL(env.APP_BASE_URL),
        tlsCert: fs.readFileSync(path.resolve(env.TLS_CERT)),
        tlsKey: fs.readFileSync(path.resolve(env.TLS_KEY)),
    });
    httpsServer.start();

    const state = crypto.randomBytes(16).toString("hex");
    const nonce = crypto.randomBytes(16).toString("hex");

    // Generate authorization URL.
    const authorizationUrl = await openIdClient.authorizationUrl(state, nonce);

    // Call authorization URL.
    let response = await httpsClient.get(authorizationUrl);
    let responseUrl = new URL(response.config.url ?? "");

    // Get promise to next server request.
    let serverRequest = httpsServer.once();

    // Log in.
    if (response.status === 200 && responseUrl.toString().includes("wp-login.php")) {
        response = await httpsClient.post(new URL(`${env.ISSUER_URL}/wp-login.php`), {
            testcookie: "1",
            log: env.WORDPRESS_USER,
            pwd: env.WORDPRESS_PASS,
            redirect_to: responseUrl.searchParams.get("redirect_to"),
        });
    }

    // Grant authorization.
    await grantAuthorization(httpsClient, env.ISSUER_URL ?? "", response);

    // Get access token.
    const request = await serverRequest;
    const tokenSet = await openIdClient.exchangeCodeForToken(request);
    const jwt = parseJwt(tokenSet.id_token ?? "");
    console.log("JWT token", jwt);

    // Get userinfo.
    const userinfo = await openIdClient.userinfo(tokenSet.access_token ?? "");
    console.debug("userinfo", userinfo);

    // Check JWT token.
    if (jwt.iss !== env.ISSUER_URL) {
        throw `JWT token iss doesn't match. Expected '${env.ISSUER_URL}', got '${jwt.iss}'`;
    }
    if (jwt.sub !== env.WORDPRESS_USER) {
        throw `JWT token sub doesn't match. Expected '${env.WORDPRESS_USER}', got '${jwt.sub}'`;
    }
    if (jwt.aud !== env.CLIENT_ID) {
        throw `JWT token aud doesn't match. Expected '${env.CLIENT_ID}', got '${jwt.aud}'`;
    }

    // Check userinfo response.
    if (userinfo.scope !== "openid profile") {
        throw `Userinfo scope doesn't match. Expected 'openid profile', got '${userinfo.scope}'`;
    }
    if (userinfo.sub !== env.WORDPRESS_USER) {
        throw `Userinfo sub doesn't match. Expected ${env.WORDPRESS_USER}, got '${userinfo.sub}'`;
    }

    console.info("Tests passed");
}

async function grantAuthorization(httpsClient: HttpsClient, issuerUrl: string, response: AxiosResponse): Promise<AxiosResponse> {
    const authorizeButtonMarkup = '<input type="submit" name="authorize" class="button button-primary button-large" value="Authorize"/>';
    if (response.status !== 200 || !response.data.includes(authorizeButtonMarkup)) {
        // Nothing to do, we were not shown the grant authorization screen.
        return response;
    }

    const html = parseHtml(response.data);
    let inputFields = html.querySelector("form")?.querySelectorAll("input");
    if (!inputFields || inputFields.length === 0) {
        throw "Authorization form not found";
    }

    inputFields = inputFields.filter(field => ["hidden", "submit"].includes(field.attrs.type));
    const params = {};
    // @ts-ignore
    inputFields.forEach(field => params[field.attrs.name] = field.attrs.value);

    return httpsClient.post(new URL(`${issuerUrl}/wp-json/openid-connect/authorize`), params);
}

function parseJwt(token: string) {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    return JSON.parse(jsonPayload);
}

void run().catch(error => {
    console.error("Tests failed:", error);
    process.exit(1);
}).finally(() => {
    if (httpsServer) {
        void httpsServer.stop();
    }
});
