import https from "node:https";
import axios, {AxiosError, AxiosInstance, AxiosResponse} from "axios";

type Options = {
    caCert: Buffer,
}

export class HttpsClient {
    private readonly axios: AxiosInstance;

    constructor(private readonly options: Options) {
        this.axios = axios.create({
            httpsAgent: new https.Agent({ ca: this.options.caCert }),
            maxRedirects: 0, // Don't follow redirects.
        });
    }

    async get(url: URL): Promise<AxiosResponse> {
        try {
            return await this.axios.get(url.toString())
        } catch (error) {
            const response = (error as AxiosError).response;
            if (response?.status === 302) {
                // Not an error.
                return response;
            }
            throw error;
        }
    }
}
