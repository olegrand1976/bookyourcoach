const useToast = () => {
  const showToast = (message, options = {}) => {
    console.log(`[${options.type || "info"}] ${options.title || ""}: ${message}`);
  };
  const success = (message, title) => {
    showToast(message, { type: "success", title });
  };
  const error = (message, title) => {
    showToast(message, { type: "error", title });
  };
  const warning = (message, title) => {
    showToast(message, { type: "warning", title });
  };
  const info = (message, title) => {
    showToast(message, { type: "info", title });
  };
  return {
    showToast,
    success,
    error,
    warning,
    info
  };
};

export { useToast as u };
//# sourceMappingURL=useToast-eUAzhXp6.mjs.map
