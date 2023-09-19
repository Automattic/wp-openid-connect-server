import * as dotenv from "dotenv"
import * as fs from "fs";
import path from "node:path";
import {OpenIdClient} from "./src/OpenIdClient";
import {Server} from "./src/Server";
import http from "http";
import {HttpTerminator} from "http-terminator";
import * as https from "https";
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

    const client = new OpenIdClient({
        issuerUrl: env.ISSUER_URL,
        clientId: env.CLIENT_ID,
        clientSecret: env.CLIENT_SECRET,
        redirectUri: env.APP_BASE_URL,
        caCertAbsolutePath: path.resolve(env.TLS_CA_CERT),
    });

    // Generate authorization URL.
    const authorizationUrl = await client.authorizationUrl();
    console.debug(`Got authorization URL: ${authorizationUrl}`);

    // Call authorization URL.
    const httpsClient = new HttpsClient({
        caCertAbsolutePath: path.resolve(env.TLS_CA_CERT),
    })
    const response = await httpsClient.get(new URL(authorizationUrl));
    console.debug(response.statusCode, response.statusMessage);

    // Handle redirect after authorization is granted.
    // const server = new Server({
    //     baseUrl: env.APP_BASE_URL,
    //     tlsCertAbsolutePath: path.resolve(env.TLS_CERT),
    //     tlsKeyAbsolutePath: path.resolve(env.TLS_KEY),
    //     requestListener: handleRequest,
    // })
    // server.start();
}

function handleRequest(request: http.IncomingMessage, response: http.ServerResponse, terminator: HttpTerminator) {
    void terminator.terminate();
}

void run();
