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

    async authorizationUrl(state: string): Promise<URL> {
        await this.init();
        if (!this.client) {
            throw "Something is wrong, client is not defined";
        }

        const url = this.client.authorizationUrl({
            scope: "openid profile",
            state,
        });

        return new URL(url);
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
