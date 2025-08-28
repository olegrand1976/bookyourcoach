import { type Ref } from '#imports';
import type { I18nHeadMetaInfo, I18nHeadOptions, SeoAttributesOptions } from '#internal-i18n-types';
import type { ComposableContext } from '../utils.js';
import type { I18nRouteMeta } from '../types.js';
/**
 * Returns localized head properties for locale-related aspects.
 *
 * @param ctx - Context used internally by composable functions.
 * @param options - An options, see about details {@link I18nHeadOptions}.
 *
 * @returns The localized {@link I18nHeadMetaInfo | head properties}.
 *
 * @public
 */
export declare function localeHead(ctx: ComposableContext, { dir, lang, seo }: I18nHeadOptions): I18nHeadMetaInfo;
export declare function _useLocaleHead(ctx: ComposableContext, options: Required<I18nHeadOptions>): Ref<I18nHeadMetaInfo>;
export declare function _useSetI18nParams(ctx: ComposableContext, seo?: SeoAttributesOptions, router?: import("vue-router").Router): (params: I18nRouteMeta) => void;
