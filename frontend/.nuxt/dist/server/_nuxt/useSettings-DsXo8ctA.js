import { ref } from "vue";
import { b as useNuxtApp } from "../server.mjs";
const useSettings = () => {
  const settings = ref({
    platform_name: "BookYourCoach",
    contact_email: "contact@bookyourcoach.fr",
    contact_phone: "+32 2 123 45 67",
    company_address: "Rue de l'Équitation 123\n1000 Bruxelles\nBelgique",
    timezone: "Europe/Brussels",
    logo_url: "/logo.png",
    favicon_url: "/favicon.ico"
  });
  const loadSettings = async () => {
    try {
      const { $api } = useNuxtApp();
      const response = await $api.get("/admin/settings/general");
      if (response.data) {
        settings.value = { ...settings.value, ...response.data };
      }
    } catch (error) {
      console.warn("Impossible de charger les paramètres:", error);
    }
  };
  const saveSettings = async (newSettings) => {
    var _a;
    try {
      const { $api } = useNuxtApp();
      const response = await $api.put("/admin/settings/general", newSettings);
      if ((_a = response.data) == null ? void 0 : _a.message) {
        settings.value = { ...settings.value, ...newSettings };
        console.log("✅ Paramètres sauvegardés:", response.data.message);
        return true;
      }
      return false;
    } catch (error) {
      console.error("❌ Erreur lors de la sauvegarde:", error);
      return false;
    }
  };
  return {
    settings,
    // Retiré: readonly()
    loadSettings,
    saveSettings
  };
};
export {
  useSettings as u
};
//# sourceMappingURL=useSettings-DsXo8ctA.js.map
