import * as https from "https";
import fs from "fs";
import {IncomingMessage} from "node:http";

type Options = {
    caCertAbsolutePath: string,
}

export class HttpsClient {
    constructor(private readonly options: Options) {
    }

    async get(url: URL): Promise<IncomingMessage> {
        return new Promise ((resolve, reject) => {
            const request = https.get({
                method: "GET",
                ca: fs.readFileSync(this.options.caCertAbsolutePath),
                hostname: url.hostname,
                port: url.port,
                path: url.pathname,
                search: url.search,
            });

            request.on('response', response => {
                resolve(response);
            });
            request.on('error', error => {
                reject(error);
            });
            request.end();
        });
    }
}
