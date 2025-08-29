import { ref, computed, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderClass, ssrInterpolate } from "vue/server-renderer";
import { u as useSettings } from "./useSettings-jytZSqGU.js";
const _sfc_main = {
  __name: "Logo",
  __ssrInlineRender: true,
  props: {
    size: {
      type: String,
      default: "md",
      validator: (value) => ["sm", "md", "lg"].includes(value)
    }
  },
  setup(__props) {
    const { settings, loadSettings } = useSettings();
    const showFallback = ref(false);
    const logoUrl = computed(() => settings.value.logo_url || "/logo.svg");
    const platformName = computed(() => settings.value.platform_name || "BookYourCoach");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex items-center" }, _attrs))}><img${ssrRenderAttr("src", unref(logoUrl))}${ssrRenderAttr("alt", unref(platformName))} class="${ssrRenderClass([
        "h-auto",
        __props.size === "sm" ? "w-24" : __props.size === "lg" ? "w-40" : "w-32"
      ])}">`);
      if (unref(showFallback)) {
        _push(`<span class="${ssrRenderClass([
          "font-serif font-bold text-primary-600",
          __props.size === "sm" ? "text-lg" : __props.size === "lg" ? "text-3xl" : "text-2xl"
        ])}">${ssrInterpolate(unref(platformName))}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Logo.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
//# sourceMappingURL=Logo-DB4hCrvh.js.map
