import { _ as __nuxt_component_0 } from "./nuxt-link-4z5Qc0yN.js";
import { ref, withAsyncContext, computed, resolveComponent, unref, withCtx, createTextVNode, createVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrRenderComponent, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { b as useI18n, u as useHead } from "../server.mjs";
import { u as useFetch } from "./fetch-CFnAc-Ix.js";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "@vue/devtools-api";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
import "@vue/shared";
import "/workspace/frontend/node_modules/perfect-debounce/dist/index.mjs";
const _sfc_main = {
  __name: "teachers",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const { t } = useI18n();
    useHead({
      title: "Nos Instructeurs | BookYourCoach",
      meta: [
        { name: "description", content: "Découvrez nos instructeurs équestres expérimentés et passionnés. Trouvez le coach parfait pour vos cours d'équitation." },
        { name: "keywords", content: "instructeurs équestres, coaches équitation, cours d'équitation, dressage, saut d'obstacles" }
      ]
    });
    const searchQuery = ref("");
    const selectedDiscipline = ref("");
    const selectedLevel = ref("");
    const { data: teachers2, pending, error, refresh } = ([__temp, __restore] = withAsyncContext(() => useFetch("/api/teachers", {
      server: false,
      default: () => [],
      transform: (data) => (data == null ? void 0 : data.data) || []
    }, "$vakz2xxlZV")), __temp = await __temp, __restore(), __temp);
    const fallbackTeachers = [
      {
        id: 1,
        first_name: "Marie",
        last_name: "Dubois",
        title: "Instructrice certifiée BPJEPS",
        bio: "Passionnée d'équitation depuis plus de 15 ans, je me spécialise dans le dressage et l'accompagnement des cavaliers débutants.",
        specialities: ["dressage", "beginner"],
        hourly_rate: 45,
        is_available: true,
        profile_photo_url: null
      },
      {
        id: 2,
        first_name: "Pierre",
        last_name: "Martin",
        title: "Coach en saut d'obstacles",
        bio: "Ancien cavalier de compétition, je transmets maintenant ma passion pour le saut d'obstacles et aide mes élèves à progresser.",
        specialities: ["jumping", "competition"],
        hourly_rate: 55,
        is_available: true,
        profile_photo_url: null
      },
      {
        id: 3,
        first_name: "Sophie",
        last_name: "Bernard",
        title: "Instructrice polyvalente",
        bio: "Formatrice expérimentée en équitation western et classique. J'accompagne tous les niveaux avec patience et bienveillance.",
        specialities: ["western", "dressage", "intermediate"],
        hourly_rate: 50,
        is_available: false,
        profile_photo_url: null
      }
    ];
    const displayTeachers = computed(() => {
      return teachers2.value && teachers2.value.length > 0 ? teachers2.value : fallbackTeachers;
    });
    const filteredTeachers = computed(() => {
      let filtered = displayTeachers.value;
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
          (teacher) => {
            var _a;
            return `${teacher.first_name} ${teacher.last_name}`.toLowerCase().includes(query) || ((_a = teacher.bio) == null ? void 0 : _a.toLowerCase().includes(query));
          }
        );
      }
      if (selectedDiscipline.value) {
        filtered = filtered.filter(
          (teacher) => {
            var _a;
            return (_a = teacher.specialities) == null ? void 0 : _a.includes(selectedDiscipline.value);
          }
        );
      }
      if (selectedLevel.value) {
        filtered = filtered.filter(
          (teacher) => {
            var _a;
            return (_a = teacher.specialities) == null ? void 0 : _a.includes(selectedLevel.value);
          }
        );
      }
      return filtered;
    });
    const formatSpeciality = (speciality) => {
      const specialityMap = {
        "dressage": "Dressage",
        "jumping": "Saut d'obstacles",
        "cross": "Cross",
        "pony_games": "Pony Games",
        "western": "Western",
        "beginner": "Débutant",
        "intermediate": "Intermédiaire",
        "advanced": "Avancé",
        "competition": "Compétition"
      };
      return specialityMap[speciality] || speciality;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Icon = resolveComponent("Icon");
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)} data-v-97562392><section class="bg-gradient-to-br from-equestrian-brown to-equestrian-darkBrown text-white py-16" data-v-97562392><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-v-97562392><div class="text-center" data-v-97562392><h1 class="text-4xl md:text-5xl font-bold mb-6 font-serif" data-v-97562392> 🐎 ${ssrInterpolate(unref(t)("teachers.title"))}</h1><p class="text-xl text-equestrian-cream max-w-3xl mx-auto" data-v-97562392>${ssrInterpolate(unref(t)("teachers.subtitle"))}</p></div></div></section><section class="py-8 bg-gray-50" data-v-97562392><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-v-97562392><div class="flex flex-col md:flex-row gap-4 items-center justify-between" data-v-97562392><div class="flex-1 max-w-md" data-v-97562392><div class="relative" data-v-97562392><input${ssrRenderAttr("value", unref(searchQuery))} type="text"${ssrRenderAttr("placeholder", unref(t)("teachers.searchPlaceholder"))} class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-equestrian-brown" data-v-97562392><div class="absolute inset-y-0 right-0 pr-3 flex items-center" data-v-97562392>`);
      _push(ssrRenderComponent(_component_Icon, {
        name: "heroicons:magnifying-glass",
        class: "h-5 w-5 text-gray-400"
      }, null, _parent));
      _push(`</div></div></div><div class="flex gap-4" data-v-97562392><select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-equestrian-brown" data-v-97562392><option value="" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "") : ssrLooseEqual(unref(selectedDiscipline), "")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.allDisciplines"))}</option><option value="dressage" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "dressage") : ssrLooseEqual(unref(selectedDiscipline), "dressage")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.dressage"))}</option><option value="jumping" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "jumping") : ssrLooseEqual(unref(selectedDiscipline), "jumping")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.jumping"))}</option><option value="cross" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "cross") : ssrLooseEqual(unref(selectedDiscipline), "cross")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.cross"))}</option><option value="pony_games" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "pony_games") : ssrLooseEqual(unref(selectedDiscipline), "pony_games")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.ponyGames"))}</option><option value="western" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDiscipline)) ? ssrLooseContain(unref(selectedDiscipline), "western") : ssrLooseEqual(unref(selectedDiscipline), "western")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.western"))}</option></select><select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-equestrian-brown" data-v-97562392><option value="" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedLevel)) ? ssrLooseContain(unref(selectedLevel), "") : ssrLooseEqual(unref(selectedLevel), "")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.allLevels"))}</option><option value="beginner" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedLevel)) ? ssrLooseContain(unref(selectedLevel), "beginner") : ssrLooseEqual(unref(selectedLevel), "beginner")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.beginner"))}</option><option value="intermediate" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedLevel)) ? ssrLooseContain(unref(selectedLevel), "intermediate") : ssrLooseEqual(unref(selectedLevel), "intermediate")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.intermediate"))}</option><option value="advanced" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedLevel)) ? ssrLooseContain(unref(selectedLevel), "advanced") : ssrLooseEqual(unref(selectedLevel), "advanced")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.advanced"))}</option><option value="competition" data-v-97562392${ssrIncludeBooleanAttr(Array.isArray(unref(selectedLevel)) ? ssrLooseContain(unref(selectedLevel), "competition") : ssrLooseEqual(unref(selectedLevel), "competition")) ? " selected" : ""}>${ssrInterpolate(unref(t)("teachers.competition"))}</option></select></div></div></div></section><section class="py-12" data-v-97562392><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-v-97562392>`);
      if (unref(pending)) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-v-97562392><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="animate-pulse" data-v-97562392><div class="bg-gray-200 rounded-lg h-64" data-v-97562392></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(error)) {
        _push(`<div class="text-center py-12" data-v-97562392><div class="text-red-600 mb-4" data-v-97562392>`);
        _push(ssrRenderComponent(_component_Icon, {
          name: "heroicons:exclamation-triangle",
          class: "h-12 w-12 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-semibold" data-v-97562392>Erreur de chargement</h3><p class="text-gray-600" data-v-97562392>${ssrInterpolate(unref(error).message)}</p></div><button class="btn-primary bg-equestrian-brown text-white" data-v-97562392> Réessayer </button></div>`);
      } else if (unref(filteredTeachers).length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-v-97562392><!--[-->`);
        ssrRenderList(unref(filteredTeachers), (teacher) => {
          _push(`<div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300" data-v-97562392><div class="relative h-48 bg-gradient-to-br from-equestrian-cream to-equestrian-lightBrown" data-v-97562392>`);
          if (teacher.profile_photo_url) {
            _push(`<img${ssrRenderAttr("src", teacher.profile_photo_url)}${ssrRenderAttr("alt", `Photo de ${teacher.first_name} ${teacher.last_name}`)} class="w-full h-full object-cover" data-v-97562392>`);
          } else {
            _push(`<div class="flex items-center justify-center h-full" data-v-97562392>`);
            _push(ssrRenderComponent(_component_Icon, {
              name: "heroicons:user-circle",
              class: "h-20 w-20 text-equestrian-brown"
            }, null, _parent));
            _push(`</div>`);
          }
          _push(`<div class="absolute top-4 right-4" data-v-97562392><span class="${ssrRenderClass([
            "px-2 py-1 text-xs font-semibold rounded-full",
            teacher.is_available ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
          ])}" data-v-97562392>${ssrInterpolate(teacher.is_available ? "Disponible" : "Occupé")}</span></div></div><div class="p-6" data-v-97562392><div class="mb-4" data-v-97562392><h3 class="text-xl font-bold text-equestrian-darkBrown mb-1" data-v-97562392>${ssrInterpolate(teacher.first_name)} ${ssrInterpolate(teacher.last_name)}</h3><p class="text-equestrian-brown" data-v-97562392>${ssrInterpolate(teacher.title || "Instructeur équestre")}</p></div>`);
          if (teacher.specialities && teacher.specialities.length) {
            _push(`<div class="mb-4" data-v-97562392><div class="flex flex-wrap gap-2" data-v-97562392><!--[-->`);
            ssrRenderList(teacher.specialities.slice(0, 3), (speciality) => {
              _push(`<span class="px-2 py-1 bg-equestrian-cream text-equestrian-darkBrown text-xs rounded-full" data-v-97562392>${ssrInterpolate(formatSpeciality(speciality))}</span>`);
            });
            _push(`<!--]-->`);
            if (teacher.specialities.length > 3) {
              _push(`<span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full" data-v-97562392> +${ssrInterpolate(teacher.specialities.length - 3)}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (teacher.bio) {
            _push(`<p class="text-gray-600 text-sm mb-4 line-clamp-3" data-v-97562392>${ssrInterpolate(teacher.bio)}</p>`);
          } else {
            _push(`<!---->`);
          }
          if (teacher.hourly_rate) {
            _push(`<div class="mb-4" data-v-97562392><span class="text-2xl font-bold text-equestrian-brown" data-v-97562392>${ssrInterpolate(teacher.hourly_rate)}€ </span><span class="text-gray-500" data-v-97562392>/heure</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex gap-2" data-v-97562392>`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/teachers/${teacher.id}`,
            class: "flex-1 btn-primary bg-equestrian-brown text-white text-center"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Voir le profil `);
              } else {
                return [
                  createTextVNode(" Voir le profil ")
                ];
              }
            }),
            _: 2
          }, _parent));
          if (teacher.is_available) {
            _push(`<button class="btn-secondary border-equestrian-brown text-equestrian-brown hover:bg-equestrian-brown hover:text-white" data-v-97562392> Réserver </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-12" data-v-97562392>`);
        _push(ssrRenderComponent(_component_Icon, {
          name: "heroicons:user-group",
          class: "h-16 w-16 text-gray-400 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-semibold text-gray-600 mb-2" data-v-97562392>${ssrInterpolate(unref(t)("teachers.noTeachers"))}</h3><p class="text-gray-500" data-v-97562392>${ssrInterpolate(unref(t)("common.search"))}</p></div>`);
      }
      _push(`</div></section><section class="py-16 bg-equestrian-lightBrown" data-v-97562392><div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8" data-v-97562392><h2 class="text-3xl font-bold text-equestrian-darkBrown mb-4" data-v-97562392> Vous êtes instructeur ? </h2><p class="text-xl text-equestrian-brown mb-8" data-v-97562392> Rejoignez notre plateforme et partagez votre passion pour l&#39;équitation </p>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register?type=teacher",
        class: "btn-primary bg-equestrian-brown text-white inline-flex items-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_Icon, {
              name: "heroicons:academic-cap",
              class: "h-5 w-5 mr-2"
            }, null, _parent2, _scopeId));
            _push2(` Devenir instructeur `);
          } else {
            return [
              createVNode(_component_Icon, {
                name: "heroicons:academic-cap",
                class: "h-5 w-5 mr-2"
              }),
              createTextVNode(" Devenir instructeur ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></section></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teachers.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const teachers = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-97562392"]]);
export {
  teachers as default
};
//# sourceMappingURL=teachers-C7QjHpYN.js.map
