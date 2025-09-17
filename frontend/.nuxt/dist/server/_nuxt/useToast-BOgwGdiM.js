const useToast = () => {
  const showToast = (message, options = {}) => {
    const type = options.type || "info";
    const title = options.title || type.charAt(0).toUpperCase() + type.slice(1);
    console.log(`[${type}] ${title}: ${message}`);
    if (type === "error") {
      alert(`❌ ${title}: ${message}`);
    }
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
export {
  useToast as u
};
//# sourceMappingURL=useToast-BOgwGdiM.js.map
