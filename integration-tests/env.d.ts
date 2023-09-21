declare global {
    namespace NodeJS {
        interface ProcessEnv {
            ISSUER_URL: string,
            CLIENT_ID: string,
            CLIENT_SECRET: string,
            TLS_CA_CERT: string,
            TLS_CERT: string,
            TLS_KEY: string,
            APP_BASE_URL: string,
            WORDPRESS_USER: string,
            WORDPRESS_PASS: string,
        }
    }
}

export {}
