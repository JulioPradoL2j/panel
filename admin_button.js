document.addEventListener("DOMContentLoaded", () => {
  const access = window.USER_ACCESS_LEVEL || 0;

  if (access >= 1) {
    const btn = document.createElement("div");
    btn.style.position = "fixed";
    btn.style.bottom = "20px";
    btn.style.right = "20px";
    btn.style.zIndex = "9999";
    btn.style.padding = "10px 15px";
    btn.style.backgroundColor = "#f5c261";
    btn.style.color = "#000";
    btn.style.fontWeight = "bold";
    btn.style.borderRadius = "8px";
    btn.style.boxShadow = "0 0 10px rgba(0,0,0,0.5)";
    btn.style.cursor = "pointer";
    btn.innerText = access === 1 ? "Painel Admin" : "Painel GM";

    btn.onclick = () => {
      window.location.href = "admin/index.php";
    };

    document.body.appendChild(btn);
  }
});
