import * as https from "https";
import fs from "fs";
import {Server as BaseServer} from "node:https";
import * as http from "http";
import {createHttpTerminator, HttpTerminator} from "http-terminator";

type Options = {
    baseUrl: string,
    tlsCertAbsolutePath: string,
    tlsKeyAbsolutePath: string,
    requestListener: (request: http.IncomingMessage, response: http.ServerResponse, terminator: HttpTerminator) => void,
};

export class Server {
    private readonly server: BaseServer<typeof http.IncomingMessage, typeof http.ServerResponse>;
    private readonly terminator: HttpTerminator;

    constructor(private readonly options: Options) {
        this.server = https.createServer({
            key: fs.readFileSync(options.tlsKeyAbsolutePath, "utf8"),
            cert: fs.readFileSync(options.tlsCertAbsolutePath, "utf8"),
        }, (request, response) => {
            options.requestListener(request, response, this.terminator)
        });
        this.terminator = createHttpTerminator({server: this.server});
    }

    start() {
        const baseUrl = new URL(this.options.baseUrl);
        // @ts-ignore
        this.server.listen(baseUrl.port, baseUrl.hostname, () => {
            console.info(`Server listening at ${baseUrl}`);
        });
    }
}
