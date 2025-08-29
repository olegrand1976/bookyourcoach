import { isObject } from "@intlify/shared";
import { useLocaleRoute } from "#i18n";
import { defineComponent, computed, h } from "vue";
import { NuxtLink } from "#components";
import { hasProtocol } from "ufo";
export default defineComponent({
  name: "NuxtLinkLocale",
  props: {
    ...NuxtLink.props,
    locale: {
      type: String,
      default: void 0,
      required: false
    }
  },
  setup(props, { slots }) {
    const localeRoute = useLocaleRoute();
    function checkPropConflicts(props2, main, sub) {
      if (import.meta.dev && props2[main] !== void 0 && props2[sub] !== void 0) {
        console.warn(`[NuxtLinkLocale] \`${main}\` and \`${sub}\` cannot be used together. \`${sub}\` will be ignored.`);
      }
    }
    const isAbsoluteUrl = computed(() => {
      const path = props.to || props.href || "";
      return typeof path === "string" && hasProtocol(path, { acceptRelative: true });
    });
    const resolvedPath = computed(() => {
      const destination = props.to ?? props.href;
      const resolved = destination != null ? localeRoute(destination, props.locale) : destination;
      if (resolved && isObject(props.to)) {
        resolved.state = props.to?.state;
      }
      return destination != null ? resolved : destination;
    });
    const isExternal = computed(() => {
      if (props.external) {
        return true;
      }
      const path = props.to || props.href || "";
      if (isObject(path)) {
        return false;
      }
      return path === "" || isAbsoluteUrl.value;
    });
    const getNuxtLinkProps = () => {
      const _props = {
        ...props
      };
      if (!isExternal.value) {
        _props.to = resolvedPath.value;
      }
      checkPropConflicts(props, "to", "href");
      delete _props.href;
      delete _props.locale;
      return _props;
    };
    return () => h(NuxtLink, getNuxtLinkProps(), slots.default);
  }
});
