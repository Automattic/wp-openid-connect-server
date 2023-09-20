import https from "node:https";
import axios, {AxiosInstance, AxiosResponse} from "axios";

type Options = {
    caCert: Buffer,
}

export class HttpsClient {
    private readonly axios: AxiosInstance;

    constructor(private readonly options: Options) {
        this.axios = axios.create({
            httpsAgent: new https.Agent({ ca: this.options.caCert }),
            // maxRedirects: 0,        // Don't follow redirects.
            withCredentials: true,  // Use cookies.
        });

        this.axios.interceptors.request.use(request => {
            console.log(request.headers)
            return request;
        });
    }

    setCookies(response: AxiosResponse) {
        console.debug(response.headers["set-cookie"]);
        this.axios.defaults.headers.put .Cookie = response.headers["set-cookie"];
    }

    async get(url: URL): Promise<AxiosResponse> {
        return this.axios.get(url.toString());
    }

    async post(url: URL, data: object): Promise<AxiosResponse> {
        const formData = new FormData();
        for (const property in data) {
            // @ts-ignore
            formData.append(property, data[property]);
        }
        console.debug(formData);
        return this.axios.post(url.toString(), formData);
    }
}
