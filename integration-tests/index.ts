import fs from "node:fs";
import path from "node:path";
import dotenv from "dotenv"
import {OpenIdClient} from "./src/OpenIdClient";
import {Server} from "./src/Server";
import {HttpsClient} from "./src/HttpsClient";
import crypto from "crypto";

dotenv.config({ path: ".env" });
if (fs.existsSync(".env.local")) {
    dotenv.config({ path: ".env.local", override: true });
}

async function run() {
    const env = process.env;
    if (!env.ISSUER_URL || !env.CLIENT_ID || !env.CLIENT_SECRET || !env.TLS_CA_CERT || !env.TLS_CERT || !env.TLS_KEY || !env.APP_BASE_URL) {
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

    const httpServer = new Server({
        baseUrl: new URL(env.APP_BASE_URL),
        tlsCert: fs.readFileSync(path.resolve(env.TLS_CERT)),
        tlsKey: fs.readFileSync(path.resolve(env.TLS_KEY)),
    });

    const state = crypto.randomBytes(16).toString("hex");

    // Generate authorization URL.
    const authorizationUrl = await openIdClient.authorizationUrl(state);

    // Call authorization URL.
    console.info("Calling authorization URL", authorizationUrl);
    const authorizeResponse = await httpsClient.get(new URL(authorizationUrl));
    const redirectUrl = authorizeResponse.headers.location;
    if (authorizeResponse.status !== 302 || !redirectUrl || redirectUrl.includes("error=")) {
        console.error(authorizeResponse.headers)
        throw `Authorization failed: ${authorizeResponse.status} ${authorizeResponse.statusText}, ${redirectUrl}`;
    }

    if (redirectUrl.includes("wp-login.php")) {
        throw `Authorization failed: user is not logged in. ${redirectUrl}`;
    }

    console.info(`Authorization granted, redirecting to ${redirectUrl}`);

    // Redirect in a bit, so we give the httpServer time to boot.
    setTimeout(async () => {
        await httpsClient.get(new URL(redirectUrl));
    }, 100);

    // Handle the redirect after authorization.
    const request = await httpServer.once();
    console.debug(request);
}

void run().catch(error => {
    console.error(error);
    process.exit(1);
});
