/**
 * Utility type to pick properties from an object based on a list of keys,
 */
type NestedPick<T, K extends string> = {
    [P in K extends `${infer A}.${string}` ? A : K & keyof T]: P extends `${infer A}.${infer B}` ? A extends keyof T ? T[A] extends object ? NestedPick<T[A], B> : never : never : P extends keyof T ? T[P] : never;
};
/**
 * Picks properties from an object based on a list of keys, allowing for
 * nested keys using dot notation.
 */
export declare function pickNested<T extends object, K extends string>(keys: K[], obj: T): Partial<NestedPick<T, K>>;
/**
 * Helper function to get a value from an object using dot notation.
 */
export declare function getNestedValue<T extends object, K extends string>(obj: T, key: K): any;
/**
 * Helper function to set a value in an object using dot notation, creating
 * nested objects as necessary.
 */
export declare function setNestedValue<T extends object, K extends string, V = any>(obj: T, key: K, value: V): void;
export {};
