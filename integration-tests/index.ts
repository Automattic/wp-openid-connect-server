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

    const httpServer = new HttpsServer({
        baseUrl: new URL(env.APP_BASE_URL),
        tlsCert: fs.readFileSync(path.resolve(env.TLS_CERT)),
        tlsKey: fs.readFileSync(path.resolve(env.TLS_KEY)),
    });

    const state = crypto.randomBytes(16).toString("hex");
    const nonce = crypto.randomBytes(16).toString("hex");

    // Generate authorization URL.
    const authorizationUrl = await openIdClient.authorizationUrl(state, nonce);

    // Call authorization URL.
    let response = await httpsClient.get(authorizationUrl);
    let responseUrl = new URL(response.config.url ?? "");

    // Boot up the server so we can handle the final redirect.
    const serverRequest = httpServer.once();

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

    // Handle the redirect after authorization.
    const request = await serverRequest;
    console.debug(request.headers);
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

void run().catch(error => {
    console.error(error);
    process.exit(1);
});
