import { defineCommand } from 'citty';

const index = defineCommand({
  meta: {
    name: "module",
    description: "Manage Nuxt modules"
  },
  args: {},
  subCommands: {
    add: () => import('./add.mjs').then(function (n) { return n.b; }).then((r) => r.default || r),
    search: () => import('./search.mjs').then((r) => r.default || r)
  }
});

export { index as default };
