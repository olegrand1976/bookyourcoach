import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { _ as _sfc_main$1 } from './AddTeacherModal-DPwk20up.mjs';
import { _ as _sfc_main$2 } from './AddStudentModal-CCquKf-z.mjs';
import { ref, mergeProps, withCtx, createTextVNode, createBlock, createVNode, openBlock, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderStyle, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './server.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';

const _sfc_main = {
  __name: "dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    const club = ref(null);
    const stats = ref(null);
    const recentTeachers = ref([]);
    const recentStudents = ref([]);
    const recentLessons = ref([]);
    const showAddTeacherModal = ref(false);
    const showAddStudentModal = ref(false);
    ref(false);
    ref(false);
    const loadDashboardData = async () => {
      try {
        console.log("\u{1F504} Chargement des donn\xE9es du dashboard club...");
        const response = await $fetch("http://localhost:8081/api/club/dashboard-test");
        console.log("\u2705 Donn\xE9es re\xE7ues:", response);
        if (response.success && response.data) {
          club.value = response.data.club;
          stats.value = response.data.stats;
          recentTeachers.value = response.data.recentTeachers;
          recentStudents.value = response.data.recentStudents;
          recentLessons.value = response.data.recentLessons || [];
          console.log("\u{1F4CA} Stats charg\xE9es:", stats.value);
        } else {
          console.error("\u274C Format de r\xE9ponse invalide:", response);
        }
      } catch (error) {
        console.error("\u274C Erreur lors du chargement des donn\xE9es:", error);
      }
    };
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getStatusClass = (status) => {
      const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        confirmed: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
        cancelled: "bg-red-100 text-red-800"
      };
      return classes[status] || "bg-gray-100 text-gray-800";
    };
    const getStatusLabel = (status) => {
      const labels = {
        pending: "En attente",
        confirmed: "Confirm\xE9",
        completed: "Termin\xE9",
        cancelled: "Annul\xE9"
      };
      return labels[status] || status;
    };
    const getLevelLabel = (level) => {
      const labels = {
        debutant: "\u{1F331} D\xE9butant",
        intermediaire: "\u{1F4C8} Interm\xE9diaire",
        avance: "\u2B50 Avanc\xE9",
        expert: "\u{1F3C6} Expert"
      };
      return labels[level] || level;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
      const _component_NuxtLink = __nuxt_component_0;
      const _component_AddTeacherModal = _sfc_main$1;
      const _component_AddStudentModal = _sfc_main$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><div class="flex items-center justify-between"><div><h1 class="text-3xl font-bold text-gray-900"> Tableau de bord Club </h1><p class="mt-2 text-gray-600"> Bienvenue ${ssrInterpolate((_a = club.value) == null ? void 0 : _a.name)}, g\xE9rez votre club en un seul endroit </p></div><div class="flex items-center space-x-4"><button class="bg-gradient-to-r from-purple-500 to-pink-600 text-white px-4 py-2 rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg><span>QR Code</span></button><button class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Enseignant</span></button><button class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-4 py-2 rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>\xC9l\xE8ve</span></button></div></div></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"><div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow"><div class="flex items-center justify-between"><div class="flex items-center"><div class="p-3 bg-blue-100 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Enseignants</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(((_b = stats.value) == null ? void 0 : _b.total_teachers) || 0)}</p></div></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/club/teachers",
        class: "text-blue-600 hover:text-blue-800 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tout \u2192 `);
          } else {
            return [
              createTextVNode(" Voir tout \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow"><div class="flex items-center justify-between"><div class="flex items-center"><div class="p-3 bg-emerald-100 rounded-lg"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">\xC9l\xE8ves</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(((_c = stats.value) == null ? void 0 : _c.total_students) || 0)}</p></div></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/club/students",
        class: "text-emerald-600 hover:text-emerald-800 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tout \u2192 `);
          } else {
            return [
              createTextVNode(" Voir tout \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow"><div class="flex items-center justify-between"><div class="flex items-center"><div class="p-3 bg-purple-100 rounded-lg"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Cours totaux</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(((_d = stats.value) == null ? void 0 : _d.total_lessons) || 0)}</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(((_e = stats.value) == null ? void 0 : _e.completed_lessons) || 0)} termin\xE9s </div></div></div><div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow"><div class="flex items-center justify-between"><div class="flex items-center"><div class="p-3 bg-yellow-100 rounded-lg"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Revenus totaux</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(((_f = stats.value) == null ? void 0 : _f.total_revenue) || 0)}\u20AC</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(((_g = stats.value) == null ? void 0 : _g.monthly_revenue) || 0)}\u20AC ce mois </div></div></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"><div class="bg-white rounded-xl shadow-lg p-6"><div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-900">Taux d&#39;occupation</h3><div class="p-2 bg-indigo-100 rounded-lg"><svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div></div><div class="text-3xl font-bold text-indigo-600 mb-2">${ssrInterpolate(((_h = stats.value) == null ? void 0 : _h.occupancy_rate) || 0)}% </div><div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="${ssrRenderStyle({ width: `${((_i = stats.value) == null ? void 0 : _i.occupancy_rate) || 0}%` })}"></div></div><p class="text-sm text-gray-600 mt-2"> Cours occup\xE9s sur le total </p></div><div class="bg-white rounded-xl shadow-lg p-6"><div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-900">Prix moyen</h3><div class="p-2 bg-green-100 rounded-lg"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div></div><div class="text-3xl font-bold text-green-600 mb-2">${ssrInterpolate(((_j = stats.value) == null ? void 0 : _j.average_lesson_price) || 0)}\u20AC </div><p class="text-sm text-gray-600"> Par cours </p></div><div class="bg-white rounded-xl shadow-lg p-6"><div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3><div class="p-2 bg-orange-100 rounded-lg"><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div></div><div class="space-y-3"><button class="w-full bg-gradient-to-r from-purple-500 to-pink-600 text-white px-4 py-2 rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 font-medium flex items-center justify-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Nouveau cours</span></button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/club/profile",
        class: "w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-center space-x-2"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"${_scopeId}></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"${_scopeId}></path></svg><span${_scopeId}>Param\xE8tres</span>`);
          } else {
            return [
              (openBlock(), createBlock("svg", {
                class: "w-4 h-4",
                fill: "none",
                stroke: "currentColor",
                viewBox: "0 0 24 24"
              }, [
                createVNode("path", {
                  "stroke-linecap": "round",
                  "stroke-linejoin": "round",
                  "stroke-width": "2",
                  d: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                }),
                createVNode("path", {
                  "stroke-linecap": "round",
                  "stroke-linejoin": "round",
                  "stroke-width": "2",
                  d: "M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                })
              ])),
              createVNode("span", null, "Param\xE8tres")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8"><div class="bg-white rounded-xl shadow-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold text-gray-900">Enseignants r\xE9cents</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/club/teachers",
        class: "text-blue-600 hover:text-blue-800 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tout \u2192 `);
          } else {
            return [
              createTextVNode(" Voir tout \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="p-6">`);
      if (((_k = recentTeachers.value) == null ? void 0 : _k.length) === 0) {
        _push(`<div class="text-center text-gray-500 py-8"><svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg><p>Aucun enseignant pour le moment</p><button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"> Ajouter le premier </button></div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(recentTeachers.value.slice(0, 5), (teacher) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"><div class="flex items-center space-x-3"><div class="bg-blue-100 p-2 rounded-lg"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div><p class="font-medium text-gray-900">${ssrInterpolate(teacher.name)}</p><p class="text-sm text-gray-600">${ssrInterpolate(teacher.email)}</p></div></div><span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">${ssrInterpolate(teacher.hourly_rate)}\u20AC/h </span></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div><div class="bg-white rounded-xl shadow-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-teal-50"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold text-gray-900">\xC9l\xE8ves r\xE9cents</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/club/students",
        class: "text-emerald-600 hover:text-emerald-800 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tout \u2192 `);
          } else {
            return [
              createTextVNode(" Voir tout \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="p-6">`);
      if (((_l = recentStudents.value) == null ? void 0 : _l.length) === 0) {
        _push(`<div class="text-center text-gray-500 py-8"><svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg><p>Aucun \xE9l\xE8ve pour le moment</p><button class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"> Ajouter le premier </button></div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(recentStudents.value.slice(0, 5), (student) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"><div class="flex items-center space-x-3"><div class="bg-emerald-100 p-2 rounded-lg"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div><p class="font-medium text-gray-900">${ssrInterpolate(student.name)}</p><p class="text-sm text-gray-600">${ssrInterpolate(student.email)}</p></div></div>`);
          if (student.level) {
            _push(`<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">${ssrInterpolate(getLevelLabel(student.level))}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div></div><div class="bg-white rounded-xl shadow-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold text-gray-900">Cours r\xE9cents</h3><button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium"> Nouveau cours </button></div></div><div class="p-6">`);
      if (((_m = recentLessons.value) == null ? void 0 : _m.length) === 0) {
        _push(`<div class="text-center text-gray-500 py-8"><svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg><p>Aucun cours programm\xE9</p><button class="mt-4 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors"> Programmer le premier </button></div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(recentLessons.value.slice(0, 5), (lesson) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"><div class="flex items-center space-x-3"><div class="bg-purple-100 p-2 rounded-lg"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><div><p class="font-medium text-gray-900">${ssrInterpolate(lesson.title || "Cours")}</p><p class="text-sm text-gray-600">${ssrInterpolate(formatDate(lesson.start_time))}</p></div></div><span class="${ssrRenderClass([getStatusClass(lesson.status), "px-2 py-1 text-xs font-medium rounded-full"])}">${ssrInterpolate(getStatusLabel(lesson.status))}</span></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div></div>`);
      if (showAddTeacherModal.value) {
        _push(ssrRenderComponent(_component_AddTeacherModal, {
          onClose: ($event) => showAddTeacherModal.value = false,
          onSuccess: loadDashboardData
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (showAddStudentModal.value) {
        _push(ssrRenderComponent(_component_AddStudentModal, {
          onClose: ($event) => showAddStudentModal.value = false,
          onSuccess: loadDashboardData
        }, null, _parent));
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=dashboard-CBpHqtHs.mjs.map
