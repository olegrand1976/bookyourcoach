import { computed, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate } from "vue/server-renderer";
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
        trophy: "🏆",
        helmet: "⛑️",
        horse: "🐎",
        saddle: "🛡️",
        horseshoe: "🔧",
        user: "👤",
        users: "👥",
        calendar: "📅",
        clock: "⏰",
        star: "⭐",
        heart: "❤️",
        check: "✅",
        cross: "❌",
        warning: "⚠️",
        info: "ℹ️"
      };
      return icons[icon] || "❓";
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
export {
  _sfc_main as _
};
//# sourceMappingURL=EquestrianIcon-D77xhcCX.js.map
