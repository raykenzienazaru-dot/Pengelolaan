(() => {
  const tabs = document.querySelectorAll(".role-tab");
  const roleInput = document.querySelector("#role");
  const togglePwd = document.querySelector("#togglePwd");
  const pwd = document.querySelector("#password");

  tabs.forEach(t => {
    t.addEventListener("click", () => {
      tabs.forEach(x => {
        x.classList.remove("is-active");
        x.setAttribute("aria-selected", "false");
      });
      t.classList.add("is-active");
      t.setAttribute("aria-selected", "true");
      roleInput.value = t.dataset.role;
    });
  });

  togglePwd?.addEventListener("click", () => {
    const isPwd = pwd.type === "password";
    pwd.type = isPwd ? "text" : "password";
    togglePwd.textContent = isPwd ? "Sembunyi" : "Lihat";
  });
})();
