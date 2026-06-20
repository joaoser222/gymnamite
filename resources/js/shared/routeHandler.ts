export type RouteParamValue =
    | string
    | number
    | boolean
    | null
    | undefined
    | Array<string | number | boolean>;

export type RouteParams = Record<string, RouteParamValue>;

export type RouteHandler = string | ((...args: any[]) => string);

// Resolve placeholders como `:id` e mantém compatibilidade com resolvers legados em função.
export function resolveRoute(
    route: RouteHandler | undefined,
    params?: RouteParams,
): string | null {
    if (route === undefined) {
        return null;
    }

    if (typeof route === 'string') {
        if (params === undefined) {
            return route;
        }

        return route.replace(/:([A-Za-z0-9_]+)/g, (match, key: string) => {
            const value = params[key];

            if (value === undefined || value === null) {
                return match;
            }

            return encodeURIComponent(String(value));
        });
    }

    if (params === undefined) {
        return route();
    }

    if ('items' in params && 'visibility' in params) {
        return route(params.items, params.visibility);
    }

    if ('items' in params) {
        return route(params.items);
    }

    if ('id' in params && Object.keys(params).length === 1) {
        return route(params.id);
    }

    return route(params);
}
