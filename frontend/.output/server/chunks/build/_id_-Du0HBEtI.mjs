import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { computed, withAsyncContext, resolveComponent, unref, withCtx, createTextVNode, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { e as useRoute, u as useHead } from './server.mjs';
import { u as useFetch } from './fetch-WBiv3zCS.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';
import '@vue/shared';
import 'perfect-debounce';

const _sfc_main = {
  __name: "[id]",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const route = useRoute();
    const teacherId = route.params.id;
    useHead({
      title: computed(() => teacher.value ? `${teacher.value.first_name} ${teacher.value.last_name} | Acti'Vibe` : "Instructeur | Acti'Vibe"),
      meta: [
        {
          name: "description",
          content: computed(() => {
            var _a;
            return ((_a = teacher.value) == null ? void 0 : _a.bio) || "Profil d'instructeur \xE9questre sur Acti'Vibe";
          })
        }
      ]
    });
    const { data: teacher, pending, error } = ([__temp, __restore] = withAsyncContext(() => useFetch(`/api/teachers/${teacherId}`, {
      server: false,
      default: () => null,
      transform: (data) => data == null ? void 0 : data.data
    }, "$_WakDSylNQ")), __temp = await __temp, __restore(), __temp);
    if (!teacher.value && !pending.value) {
      const fallbackData = {
        id: parseInt(teacherId),
        first_name: "Marie",
        last_name: "Dubois",
        title: "Instructrice certifi\xE9e BPJEPS",
        bio: "Passionn\xE9e d'\xE9quitation depuis plus de 15 ans, je me sp\xE9cialise dans le dressage et l'accompagnement des cavaliers d\xE9butants. Mon approche p\xE9dagogique se base sur la confiance mutuelle entre le cavalier et sa monture. J'ai eu la chance de participer \xE0 plusieurs comp\xE9titions nationales avant de me tourner vers l'enseignement.",
        specialities: ["dressage", "beginner", "intermediate"],
        hourly_rate: 45,
        is_available: true,
        profile_photo_url: null,
        location: "Centre \xE9questre de Fontainebleau",
        experience_years: 12,
        languages: ["Fran\xE7ais", "Anglais"],
        certifications: [
          {
            id: 1,
            name: "BPJEPS \xC9quitation",
            organization: "Minist\xE8re des Sports"
          },
          {
            id: 2,
            name: "Galop 7 FFE",
            organization: "F\xE9d\xE9ration Fran\xE7aise d'\xC9quitation"
          }
        ],
        reviews: [
          {
            id: 1,
            student_name: "Claire M.",
            rating: 5,
            comment: "Excellente instructrice ! Tr\xE8s patiente et p\xE9dagogue. Mes progr\xE8s ont \xE9t\xE9 rapides gr\xE2ce \xE0 ses conseils."
          },
          {
            id: 2,
            student_name: "Thomas L.",
            rating: 5,
            comment: "Marie a su me redonner confiance apr\xE8s une chute. Je recommande vivement ses cours."
          }
        ]
      };
      teacher.value = fallbackData;
    }
    const formatSpeciality = (speciality) => {
      const specialityMap = {
        "dressage": "Dressage",
        "jumping": "Saut d'obstacles",
        "cross": "Cross",
        "pony_games": "Pony Games",
        "western": "Western",
        "beginner": "D\xE9butant",
        "intermediate": "Interm\xE9diaire",
        "advanced": "Avanc\xE9",
        "competition": "Comp\xE9tition"
      };
      return specialityMap[speciality] || speciality;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Icon = resolveComponent("Icon");
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (unref(pending)) {
        _push(`<div class="min-h-screen flex items-center justify-center"><div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div></div>`);
      } else if (unref(error)) {
        _push(`<div class="min-h-screen flex items-center justify-center"><div class="text-center">`);
        _push(ssrRenderComponent(_component_Icon, {
          name: "heroicons:exclamation-triangle",
          class: "h-16 w-16 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h1 class="text-2xl font-bold text-gray-800 mb-4">Instructeur non trouv\xE9</h1><p class="text-gray-600 mb-8">Cet instructeur n&#39;existe pas ou n&#39;est plus disponible.</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/teachers",
          class: "btn-primary bg-blue-600 text-white"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Retour aux instructeurs `);
            } else {
              return [
                createTextVNode(" Retour aux instructeurs ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else if (unref(teacher)) {
        _push(`<div><section class="bg-gradient-to-br from-blue-600 to-gray-800 text-white py-16"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex flex-col lg:flex-row items-center gap-8"><div class="flex-shrink-0"><div class="w-32 h-32 lg:w-48 lg:h-48 rounded-full overflow-hidden bg-gray-50">`);
        if (unref(teacher).profile_photo_url) {
          _push(`<img${ssrRenderAttr("src", unref(teacher).profile_photo_url)}${ssrRenderAttr("alt", `Photo de ${unref(teacher).first_name} ${unref(teacher).last_name}`)} class="w-full h-full object-cover">`);
        } else {
          _push(`<div class="flex items-center justify-center h-full">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:user-circle",
            class: "h-24 w-24 lg:h-32 lg:w-32 text-gray-700"
          }, null, _parent));
          _push(`</div>`);
        }
        _push(`</div></div><div class="flex-1 text-center lg:text-left"><h1 class="text-3xl lg:text-4xl font-bold mb-2">${ssrInterpolate(unref(teacher).first_name)} ${ssrInterpolate(unref(teacher).last_name)}</h1><p class="text-xl text-gray-100 mb-4">${ssrInterpolate(unref(teacher).title || "Instructeur \xE9questre")}</p><div class="flex flex-wrap gap-2 justify-center lg:justify-start mb-6"><!--[-->`);
        ssrRenderList(unref(teacher).specialities, (speciality) => {
          _push(`<span class="px-3 py-1 bg-gray-50 text-gray-900 text-sm rounded-full">${ssrInterpolate(formatSpeciality(speciality))}</span>`);
        });
        _push(`<!--]--></div><div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start"><span class="${ssrRenderClass([
          "px-4 py-2 text-sm font-semibold rounded-full",
          unref(teacher).is_available ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
        ])}">${ssrInterpolate(unref(teacher).is_available ? "\u2705 Disponible" : "\u274C Occup\xE9")}</span>`);
        if (unref(teacher).hourly_rate) {
          _push(`<div class="text-2xl font-bold">${ssrInterpolate(unref(teacher).hourly_rate)}\u20AC<span class="text-lg font-normal">/heure</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div></section><section class="py-12"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="grid grid-cols-1 lg:grid-cols-3 gap-8"><div class="lg:col-span-2"><div class="bg-white rounded-xl shadow-lg p-6 mb-8"><h2 class="text-2xl font-bold text-gray-900 mb-4"> \xC0 propos </h2><p class="text-gray-700 leading-relaxed">${ssrInterpolate(unref(teacher).bio || "Aucune description disponible pour cet instructeur.")}</p></div><div class="bg-white rounded-xl shadow-lg p-6 mb-8"><h2 class="text-2xl font-bold text-gray-900 mb-4"> Certifications &amp; Exp\xE9rience </h2>`);
        if (unref(teacher).certifications && unref(teacher).certifications.length) {
          _push(`<div class="space-y-3"><!--[-->`);
          ssrRenderList(unref(teacher).certifications, (cert) => {
            _push(`<div class="flex items-center p-3 bg-gray-50 rounded-lg">`);
            _push(ssrRenderComponent(_component_Icon, {
              name: "heroicons:academic-cap",
              class: "h-6 w-6 text-gray-700 mr-3"
            }, null, _parent));
            _push(`<div><h3 class="font-semibold text-gray-900">${ssrInterpolate(cert.name)}</h3><p class="text-sm text-gray-700">${ssrInterpolate(cert.organization)}</p></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="text-gray-500 italic"> Aucune certification renseign\xE9e </div>`);
        }
        _push(`</div><div class="bg-white rounded-xl shadow-lg p-6"><h2 class="text-2xl font-bold text-gray-900 mb-4"> Avis des \xE9l\xE8ves </h2>`);
        if (unref(teacher).reviews && unref(teacher).reviews.length) {
          _push(`<div class="space-y-4"><!--[-->`);
          ssrRenderList(unref(teacher).reviews.slice(0, 3), (review) => {
            _push(`<div class="border-l-4 border-blue-600 pl-4"><div class="flex items-center mb-2"><div class="flex text-yellow-400 mr-2"><!--[-->`);
            ssrRenderList(5, (star) => {
              _push(ssrRenderComponent(_component_Icon, {
                key: star,
                name: "heroicons:star-solid",
                class: [star <= review.rating ? "text-yellow-400" : "text-gray-300", "h-4 w-4"]
              }, null, _parent));
            });
            _push(`<!--]--></div><span class="text-sm text-gray-600">${ssrInterpolate(review.student_name)}</span></div><p class="text-gray-700">${ssrInterpolate(review.comment)}</p></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="text-gray-500 italic"> Aucun avis pour le moment </div>`);
        }
        _push(`</div></div><div class="space-y-6"><div class="bg-white rounded-xl shadow-lg p-6"><h3 class="text-xl font-bold text-gray-900 mb-4"> R\xE9server un cours </h3>`);
        if (unref(teacher).is_available) {
          _push(`<div class="space-y-4"><div class="text-center"><div class="text-3xl font-bold text-gray-700 mb-1">${ssrInterpolate(unref(teacher).hourly_rate || 50)}\u20AC </div><div class="text-gray-600">par heure</div></div><button class="w-full btn-primary bg-blue-600 text-white">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:calendar-days",
            class: "h-5 w-5 mr-2"
          }, null, _parent));
          _push(` R\xE9server maintenant </button><button class="w-full btn-secondary border-blue-600 text-gray-700 hover:bg-blue-700 hover:text-white">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:chat-bubble-left-right",
            class: "h-5 w-5 mr-2"
          }, null, _parent));
          _push(` Contacter </button></div>`);
        } else {
          _push(`<div class="text-center text-gray-500">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:clock",
            class: "h-12 w-12 mx-auto mb-2 text-gray-400"
          }, null, _parent));
          _push(`<p>Cet instructeur n&#39;est pas disponible actuellement</p></div>`);
        }
        _push(`</div><div class="bg-white rounded-xl shadow-lg p-6"><h3 class="text-xl font-bold text-gray-900 mb-4"> Informations </h3><div class="space-y-3">`);
        if (unref(teacher).location) {
          _push(`<div class="flex items-center">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:map-pin",
            class: "h-5 w-5 text-gray-700 mr-3"
          }, null, _parent));
          _push(`<span class="text-gray-700">${ssrInterpolate(unref(teacher).location)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(teacher).experience_years) {
          _push(`<div class="flex items-center">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:star",
            class: "h-5 w-5 text-gray-700 mr-3"
          }, null, _parent));
          _push(`<span class="text-gray-700">${ssrInterpolate(unref(teacher).experience_years)} ans d&#39;exp\xE9rience</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(teacher).languages) {
          _push(`<div class="flex items-center">`);
          _push(ssrRenderComponent(_component_Icon, {
            name: "heroicons:language",
            class: "h-5 w-5 text-gray-700 mr-3"
          }, null, _parent));
          _push(`<span class="text-gray-700">${ssrInterpolate(unref(teacher).languages.join(", "))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="bg-gray-50 rounded-xl p-6">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/teachers",
          class: "flex items-center justify-center w-full btn-secondary border-blue-600 text-gray-700 hover:bg-blue-700 hover:text-white"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_component_Icon, {
                name: "heroicons:arrow-left",
                class: "h-5 w-5 mr-2"
              }, null, _parent2, _scopeId));
              _push2(` Voir tous les instructeurs `);
            } else {
              return [
                createVNode(_component_Icon, {
                  name: "heroicons:arrow-left",
                  class: "h-5 w-5 mr-2"
                }),
                createTextVNode(" Voir tous les instructeurs ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div></div></div></section></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teachers/[id].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_id_-Du0HBEtI.mjs.map
