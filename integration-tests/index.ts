import fs from "node:fs";
import path from "node:path";
import http from "node:http";
import dotenv from "dotenv"
import {OpenIdClient} from "./src/OpenIdClient";
import {Server} from "./src/Server";
import {HttpTerminator} from "http-terminator";
import {HttpsClient} from "./src/HttpsClient";

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

    const app = new Server({
        baseUrl: new URL(env.APP_BASE_URL),
        tlsCert: fs.readFileSync(path.resolve(env.TLS_CERT)),
        tlsKey: fs.readFileSync(path.resolve(env.TLS_KEY)),
        requestListener: afterAuthorization,
    });

    const httpsClient = new HttpsClient({
        caCert,
    })

    // Generate authorization URL.
    const authorizationUrl = await openIdClient.authorizationUrl();

    // Call authorization URL.
    console.info(`Calling authorization URL: ${authorizationUrl}`);
    const authorizeResponse = await httpsClient.get(new URL(authorizationUrl));
    if (authorizeResponse.statusCode !== 301 || !authorizeResponse.headers.location) {
        console.error(authorizeResponse.headers)
        throw `Authorization failed: ${authorizeResponse.statusCode} ${authorizeResponse.statusMessage}`;
    }

    // Redirect in a bit, so we give the app time to boot.
    setTimeout(() => {
        httpsClient.get(new URL(authorizeResponse.headers.location));
    }, 100);

    app.start();
}

function afterAuthorization(request: http.IncomingMessage, response: http.ServerResponse, terminator: HttpTerminator) {
    response.end();
    void terminator.terminate();
}

void run().catch(error => console.error(error));
