document.addEventListener("DOMContentLoaded", function () {

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {

        loginForm.addEventListener("submit", async function (event) {

            event.preventDefault();

            const usuario = document.getElementById("user").value;
            const rol = document.getElementById("role").value;
            const contrasena = document.getElementById("pass").value;

            if (usuario === "" || rol === "" || contrasena === "") {
                alert("Por favor completa todos los campos.");
                return;
            }

            const datos = new FormData();
            datos.append("usuario", usuario);
            datos.append("rol", rol);
            datos.append("clave", contrasena);

            try {

                const respuesta = await fetch("login.php", {
                    method: "POST",
                    body: datos
                });

                const resultado = await respuesta.json();

                if (resultado.ok) {
                    window.location.href = resultado.redirigir;
                } else {
                    alert(resultado.mensaje || "No se pudo iniciar sesión.");
                }

            } catch (error) {
                alert("No se pudo conectar con el servidor. Verifica que Apache y MySQL estén activos en XAMPP.");
            }

        });

    }

});
