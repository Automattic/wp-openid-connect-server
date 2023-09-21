import {Client, custom as openidOptions, Issuer, TokenSet, UserinfoResponse} from "openid-client";
import {IncomingMessage} from "node:http";

type Options = {
    issuerUrl: string,
    clientId: string,
    clientSecret: string,
    redirectUri: string,
    caCert: Buffer,
};

export class OpenIdClient {
    private issuer?: Issuer;
    private client?: Client;

    constructor(private readonly options: Options) {
        openidOptions.setHttpOptionsDefaults({
            ca: options.caCert,
        });
    }

    async authorizationUrl(state: string, nonce: string): Promise<URL> {
        await this.init();
        const url = this.client?.authorizationUrl({
            scope: "openid profile",
            state,
            nonce,
        });

        return new URL(url ?? "");
    }

    async exchangeCodeForToken(request: IncomingMessage): Promise<TokenSet> {
        const params = this.client?.callbackParams(request);
        if (!params) {
            throw "Failed to parse callback params";
        }

        const tokenSet = await this.client?.grant({
            grant_type: "authorization_code",
            code: params.code,
            client_id: this.options.clientId,
            client_secret: this.options.clientId,
            redirect_uri: this.options.redirectUri,
        });

        if (!tokenSet) {
            throw "Failed to get token set";
        }

        return tokenSet;
    }

    async userinfo(token: string): Promise<UserinfoResponse> {
        const response = this.client?.userinfo(token);
        if (!response) {
            throw "Failed to get userinfo";
        }
        return response
    }

    private async init() {
        if (this.issuer) {
            return;
        }
        this.issuer = await Issuer.discover(this.options.issuerUrl);
        console.debug('Discovered issuer %s %O', this.issuer.issuer, this.issuer.metadata, "\n");

        this.client = new this.issuer.Client({
            client_id: this.options.clientId,
            client_secret: this.options.clientId,
            redirect_uris: [this.options.redirectUri],
            response_types: ["code"],
        });
    }
}
