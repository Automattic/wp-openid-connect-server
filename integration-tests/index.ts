import { Issuer } from "openid-client";
import * as dotenv from "dotenv"
import * as fs from "fs";
import { custom as openidOptions} from 'openid-client';

dotenv.config({ path: ".env" });
if (fs.existsSync(".env.local")) {
    dotenv.config({ path: ".env.local", override: true });
}

const env = process.env;
if (!env.ISSUER_URL || !env.CLIENT_ID || !env.CLIENT_SECRET || !env.TLS_CA_CERT) {
    console.error("Some or all required environment variables were not defined. Set them in the .env file.");
    process.exit(1);
}

openidOptions.setHttpOptionsDefaults({
    ca: fs.readFileSync(env.TLS_CA_CERT),
});

console.log(`Discovering issuer at ${env.ISSUER_URL}`);
const issuer = await Issuer.discover(env.ISSUER_URL);
console.log('Discovered issuer %s %O', issuer.issuer, issuer.metadata);
