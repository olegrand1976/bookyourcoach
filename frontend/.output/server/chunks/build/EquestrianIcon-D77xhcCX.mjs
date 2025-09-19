import { computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate } from 'vue/server-renderer';

const _sfc_main = {
  __name: "EquestrianIcon",
  __ssrInlineRender: true,
  props: {
    icon: {
      type: String,
      required: true
    },
    size: {
      type: Number,
      default: 16
    }
  },
  setup(__props) {
    const iconClass = computed(() => {
      return "inline-block";
    });
    const getIconEmoji = (icon) => {
      const icons = {
        trophy: "\u{1F3C6}",
        helmet: "\u26D1\uFE0F",
        horse: "\u{1F40E}",
        saddle: "\u{1F6E1}\uFE0F",
        horseshoe: "\u{1F527}",
        user: "\u{1F464}",
        users: "\u{1F465}",
        calendar: "\u{1F4C5}",
        clock: "\u23F0",
        star: "\u2B50",
        heart: "\u2764\uFE0F",
        check: "\u2705",
        cross: "\u274C",
        warning: "\u26A0\uFE0F",
        info: "\u2139\uFE0F"
      };
      return icons[icon] || "\u2753";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<span${ssrRenderAttrs(mergeProps({
        class: unref(iconClass),
        style: { fontSize: __props.size + "px" }
      }, _attrs))}>${ssrInterpolate(getIconEmoji(__props.icon))}</span>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/EquestrianIcon.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=EquestrianIcon-D77xhcCX.mjs.map
