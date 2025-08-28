import { addRspackPlugin, addWebpackPlugin, extendViteConfig, useNuxt } from "@nuxt/kit";

//#region src/index.ts
async function addDefinePlugin(define) {
	const nuxt = useNuxt();
	if (nuxt.options.builder === "@nuxt/webpack-builder") try {
		const webpack = await import("webpack").then((m) => m.default || m);
		addWebpackPlugin(new webpack.DefinePlugin(define));
	} catch (e) {
		throw new Error(`Failed to import webpack: ${e.message}`);
	}
	if (nuxt.options.builder === "@nuxt/rspack-builder") try {
		const { rspack } = await import("@rspack/core");
		addRspackPlugin(new rspack.DefinePlugin(define));
	} catch (e) {
		throw new Error(`Failed to import rspack: ${e.message}`);
	}
	extendViteConfig((config) => {
		for (const key in define) config.define[key] = define[key];
	});
}

//#endregion
export { addDefinePlugin };