import https from "node:https";
import axios, {AxiosInstance, AxiosResponse} from "axios";
const setCookieParser = require('set-cookie-parser');

type Options = {
    caCert: Buffer,
}

export class HttpsClient {
    private readonly axios: AxiosInstance;
    private cookies: string[] = [];

    constructor(private readonly options: Options) {
        this.axios = axios.create({
            httpsAgent: new https.Agent({ ca: this.options.caCert }),
            maxRedirects: 0,
            validateStatus: function (status) {
                return [200, 302].includes(status);
            }
        });

        this.axios.interceptors.response.use(response => {
            console.debug("response", response.status, response.config.url, "\n")
            return response;
        });

        this.axios.interceptors.request.use(request => {
            console.debug("request", request.url, request.data ?? "", "\n")
            return request;
        });
    }

    async get(url: URL): Promise<AxiosResponse> {
        const response = await this.axios.get(url.toString(), {
            headers: {
                Cookie: this.cookieHeader(),
            },
        });

        this.setCookies(response);

        if (response.status === 302) {
            return this.get(response.headers.location);
        }

        return response;
    }

    async post(url: URL, data: object): Promise<AxiosResponse> {
        const formData = new FormData();
        for (const property in data) {
            // @ts-ignore
            formData.append(property, data[property]);
        }

        const response = await this.axios.post(url.toString(), formData, {
            headers: {
                Cookie: this.cookieHeader(),
            },
        });

        this.setCookies(response);

        if (response.status === 302) {
            return this.get(response.headers.location);
        }

        return response
    }

    private setCookies(response: AxiosResponse) {
        const cookies = setCookieParser.parse(response);
        for (const cookie of cookies) {
            this.cookies[cookie.name] = cookie.value;
        }
    }

    private cookieHeader(): string {
        let header = "";
        for (const name in this.cookies) {
            const value = this.cookies[name];
            if (value.trim() === "") {
                continue;
            }
            if (header !== "") {
                header += "; ";
            }
            header += `${encodeURIComponent(name)}=${encodeURIComponent(this.cookies[name])}`;
        }
        return header;
    }
}
