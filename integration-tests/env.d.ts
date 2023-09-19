declare global {
    namespace NodeJS {
        interface ProcessEnv {
            ISSUER_URL: string,
            CLIENT_ID: string,
            CLIENT_SECRET: string,
            TLS_CA_CERT: string,
        }
    }
}

export {}
