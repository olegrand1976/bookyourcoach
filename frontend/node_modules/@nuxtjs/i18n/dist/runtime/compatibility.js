export function getI18nTarget(i18n) {
  return i18n != null && "global" in i18n && "mode" in i18n ? i18n.global : i18n;
}
export function getComposer(i18n) {
  const target = getI18nTarget(i18n);
  return "__composer" in target ? target.__composer : target;
}
