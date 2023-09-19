import https from "node:https";
import {IncomingMessage} from "node:http";

type Options = {
    caCert: Buffer,
}

export class HttpsClient {
    constructor(private readonly options: Options) {
    }

    async get(url: URL): Promise<IncomingMessage> {
        return new Promise ((resolve, reject) => {
            const request = https.get({
                method: "GET",
                ca: this.options.caCert,
                hostname: url.hostname,
                port: url.port,
                path: url.pathname,
                search: url.search,
            });
            request.on("response", response => resolve(response));
            request.on("error", error => reject(error));
            request.end();
        });
    }
}
