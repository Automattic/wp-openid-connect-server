import * as dotenv from "dotenv"
import * as fs from "fs";
import path from "node:path";
import {OpenIdClient} from "./src/OpenIdClient";

dotenv.config({ path: ".env" });
if (fs.existsSync(".env.local")) {
    dotenv.config({ path: ".env.local", override: true });
}

async function run() {
    const env = process.env;
    if (!env.ISSUER_URL || !env.CLIENT_ID || !env.CLIENT_SECRET || !env.TLS_CA_CERT) {
        console.error("Some or all required environment variables were not defined. Set them in the .env file.");
        process.exit(1);
    }

    const client = new OpenIdClient({
        issuerUrl: env.ISSUER_URL,
        clientId: env.CLIENT_ID,
        clientSecret: env.CLIENT_SECRET,
        redirectUri: "http://localhost:3000/cb",
        caCertAbsolutePath: path.resolve(env.TLS_CA_CERT),
    });

    const authorizationUrl = await client.authorizationUrl();
    console.debug(`Got authorization URL: ${authorizationUrl}`);
}

void run();
