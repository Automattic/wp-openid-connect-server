import {Client, custom as openidOptions, generators, Issuer} from "openid-client";

type Options = {
    issuerUrl: string,
    clientId: string,
    clientSecret: string,
    redirectUri: string,
    caCert: Buffer,
};

export class OpenIdClient {
    private readonly codeVerifier: string;
    private readonly codeChallenge: string;
    private issuer?: Issuer;
    private client?: Client;

    constructor(private readonly options: Options) {
        openidOptions.setHttpOptionsDefaults({
            ca: options.caCert,
        });
        this.codeVerifier = generators.codeVerifier();
        this.codeChallenge = generators.codeChallenge(this.codeVerifier);
    }

    async authorizationUrl(state: string): Promise<string> {
        await this.init();
        if (!this.client) {
            throw "Something is wrong, client is not defined";
        }

        return this.client.authorizationUrl({
            scope: "openid profile",
            state,
        });
    }

    private async init() {
        if (this.issuer) {
            return;
        }
        this.issuer = await Issuer.discover(this.options.issuerUrl);
        console.debug('Discovered issuer %s %O', this.issuer.issuer, this.issuer.metadata);

        this.client = new this.issuer.Client({
            client_id: this.options.clientId,
            client_secret: this.options.clientId,
            redirect_uris: [this.options.redirectUri],
            response_types: ["code"],

            // id_token_signed_response_alg (default "RS256")
            // token_endpoint_auth_method (default "client_secret_basic")
        }); // => Client
    }
}
