import { Issuer } from "openid-client";
import * as dotenv from "dotenv"
import * as fs from "fs";

process.env["NODE_TLS_REJECT_UNAUTHORIZED"] = "0";

dotenv.config({ path: ".env" });
if (fs.existsSync(".env.local")) {
    dotenv.config({ path: ".env.local", override: true });
}

const issuerUrl = process.env.ISSUER_URL;
if (!issuerUrl) {
    console.error("ISSUER_URL environment variable must be set");
    process.exit(1);
}

console.log(`Using issuer ${issuerUrl}`);

const issuer = await Issuer.discover(issuerUrl);
console.log('Discovered issuer %s %O', issuer.issuer, issuer.metadata);
