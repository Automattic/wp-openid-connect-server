import fs from "node:fs";
import path from "node:path";
import dotenv from "dotenv"
import {OpenIdClient} from "./src/OpenIdClient";
import {HttpsServer} from "./src/HttpsServer";
import {HttpsClient} from "./src/HttpsClient";
import crypto from "crypto";

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

    // Generate authorization URL.
    const authorizationUrl = await openIdClient.authorizationUrl(state);

    // Call authorization URL.
    let response = await httpsClient.get(authorizationUrl);
    let responseUrl = new URL(response.config.url ?? "");

    // Log in, if needed.
    if (response.status === 200 && responseUrl.toString().includes("wp-login.php")) {
        response = await httpsClient.post(new URL(`${env.ISSUER_URL}/wp-login.php`), {
            testcookie: "1",
            log: env.WORDPRESS_USER,
            pwd: env.WORDPRESS_PASS,
            redirect_to: responseUrl.searchParams.get("redirect_to"),
        });
    }

    // Grant authorization, if needed.
    // const authorizeButtonMarkup = '<input type="submit" name="authorize" class="button button-primary button-large" value="Authorize">';
    // if (response.status === 200 && response.data.includes(authorizeButtonMarkup)) {
    // }

    // if (response.status === 302) {
    //     const redirectUrl = response.headers.location;
    //     if (!redirectUrl || redirectUrl.includes("error=")) {
    //         throw `Authorization failed: ${response.status} ${response.statusText}, ${redirectUrl}`;
    //     }
    // }

    // console.info(`Authorization granted, redirecting to ${redirectUrl}`);
    //
    // // Redirect in a bit, so we give the httpServer time to boot.
    // setTimeout(async () => {
    //     await httpsClient.get(new URL(redirectUrl));
    // }, 100);
    //
    // // Handle the redirect after authorization.
    // const request = await httpServer.once();
    // console.debug(request);
}

void run().catch(error => {
    console.error(error);
    process.exit(1);
});
