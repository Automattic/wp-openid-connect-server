import http, {IncomingMessage, ServerResponse} from "node:http";
import https, {Server as BaseServer} from "node:https";
import {createHttpTerminator, HttpTerminator} from "http-terminator";

type Options = {
    baseUrl: URL,
    tlsCert: Buffer,
    tlsKey: Buffer,
    requestListener: (request: http.IncomingMessage, response: http.ServerResponse, terminator: HttpTerminator) => void,
};

export class Server {
    private readonly server: BaseServer<typeof http.IncomingMessage, typeof http.ServerResponse>;
    private readonly terminator: HttpTerminator;

    constructor(private readonly options: Options) {
        this.server = https.createServer({
            key: options.tlsKey,
            cert: options.tlsCert,
        }, (request, response) => {
            options.requestListener(request, response, this.terminator)
        });
        this.terminator = createHttpTerminator({server: this.server});
    }

    start() {
        // @ts-ignore
        this.server.listen(this.options.baseUrl.port, this.options.baseUrl.hostname, () => {
            console.info(`Server listening at ${this.options.baseUrl.toString()}`);
        });
    }
}
