import type { H3Event } from 'h3';
import type { CompatRoute } from '../types.js';
import type { NuxtApp } from '#app';
export declare const useDetectors: (event: H3Event | undefined, config: {
    cookieKey: string;
}, nuxtApp?: NuxtApp) => {
    cookie: () => string | undefined;
    header: () => string | undefined;
    navigator: () => string | undefined;
    host: (path: string) => string | undefined;
    route: (path: string | CompatRoute) => string | undefined;
};
