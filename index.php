<?php
session_start();

function authenticateVault() {
    $url = "https://bisbx-promomats.veevavault.com/api/v25.2/auth";

    // Igual que en Postman: form-data
    $data = [
        "username" => "integration.cdw@bisbx.com",
        "password" => "BI_Integration2026"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // array → multipart/form-data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Opcional: headers básicos (cURL calcula boundary solo)
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: */*"
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("Error en la conexión: " . $error);
    }

    $result = json_decode($response, true);

    //echo "<pre>";
    //print_r($result);
    //echo "</pre>";

    if (isset($result['responseStatus']) && $result['responseStatus'] === "SUCCESS") {
        $_SESSION['vault_sessionId'] = $result['sessionId'];
        return true;
    } else {
        return false;
    }
}

// Validar sesión
if (!isset($_SESSION['vault_sessionId'])) {
    if (!authenticateVault()) {
        die("No se pudo autenticar contra Veeva Vault.");
    }
}
?>



<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reactivate / Inactivate Account</title><link rel="icon" type="image/png" href="img/favicon.png" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-box {
      width: 90%;
      max-width: 400px;
      padding: 30px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .login-box img {
      max-width: 180px;
      margin: 10px auto;
      display: block;
    }
    .login-box h1 {
      font-size: 22px;
      margin: 15px 0;
      color: #333;
    }
    form {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    input {
      width: 100%;
      max-width: 300px;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    .btn {
      background: #f58220;
      color: #fff;
      border: none;
      padding: 14px;
      width: 100%;
      max-width: 300px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .links {
      margin-top: 10px;
      font-size: 0.9em;
    }
    .links a {
      color: #0073e6;
      text-decoration: none;
    }
    /* Responsivo */
    @media (max-width: 480px) {
      .login-box {
        padding: 20px;
      }
      .login-box img {
        max-width: 140px;
      }
      .login-box h1 {
        font-size: 18px;
      }
      input, .btn {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
    <div id="vault-alert" class="alert-success">
    Connection established with BI M&S Veeva Vault
    </div>

    <style>
      .alert-success {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #4CAF50; /* verde */
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        font-size: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        opacity: 0;
        transition: opacity 1s ease-in-out;
        z-index: 9999;
      }
    </style>
    
    <script>
      // Mostrar gradualmente
      window.addEventListener("DOMContentLoaded", function() {
        const alertBox = document.getElementById("vault-alert");
        alertBox.style.opacity = "1";
    
        // Ocultar después de 5 segundos con fade out
        setTimeout(() => {
          alertBox.style.opacity = "0";
        }, 5000);
      });
    </script>

  <div class="login-box">
    <!-- Logo Boehringer Ingelheim -->
    <img src="img/boehringer_logo.png" alt="Boehringer Ingelheim">

    <!-- Título central -->
    <h1>Reactivate / Inactivate Account</h1>

    <!-- Logo Veeva Vault -->
    <img src="img/veeva_logo.png" alt="Veeva Vault">

    <!-- Formulario -->
    
    
      
    
      
        <input type="text" id="emailInput" placeholder="Enter your email" />
        <button id="btnReactivate" class="btn">Reactivate Account</button><br><br>
        <button id="btnInactivate" class="btn">Inactivate Account</button>
     
    
      <div id="messageBox"></div>
    

    
      <!-- Contenedor para mensajes dinámicos -->
      <div id="messageBox"></div>
    </div>

    <!-- Script -->
    <script>
      const emailInput = document.getElementById("emailInput");
      const messageBox = document.getElementById("messageBox");
    
      async function handleAction(actionType) {
        const email = emailInput.value.trim();
        if (!email) return;
    
        try {
          // 1. Retrieve User by email
          const response = await fetch("api/retrieveUserByEmail.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email })
          });
    
          const data = await response.json();
    
          if (response.ok && data.success) {
            const userId = data.id;
    
            // 2. Según el botón elegido
            if (actionType === "reactivate") {
              const reactivateResp = await fetch("api/reactivateUserCopy.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: userId })
              });
    
              const reactivateData = await reactivateResp.json();
    
              if (reactivateResp.ok && reactivateData.success) {
                messageBox.innerHTML = `
                  <div style="
                    background-color:#d4edda;
                    color:#155724;
                    border-radius:8px;
                    padding:12px;
                    margin-top:12px;">
                    Account Reactivated successfully, now you can login to 
                    <a href="https://bi-vault" target="_blank" 
                       style="color:#155724; font-weight:bold; text-decoration:underline;">
                       bi-vault
                    </a>.
                  </div>
                `;
              }
            } else if (actionType === "inactivate") {
              const inactivateResp = await fetch("api/inactivateUser.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: userId })
              });
    
              const inactivateData = await inactivateResp.json();
    
              if (inactivateResp.ok && inactivateData.success) {
                messageBox.innerHTML = `
                  <div style="
                    background-color:#fff3cd;
                    color:#856404;
                    border-radius:8px;
                    padding:12px;
                    margin-top:12px;">
                    Account Inactivated successfully.
                  </div>
                `;
              }
            }
    
          } else {
            // 3. Usuario no encontrado → mensaje rojo con bullets
            messageBox.innerHTML = `
              <div style="
                background-color:#f8d7da;
                color:#721c24;
                border-radius:8px;
                padding:12px;
                margin-top:12px;">
                <p>User does not exist in the selected environment, please go the following MyServices form to request your account:</p>
                <ul style="margin-left:20px;">
                  <li>
                    <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=f020f7cc47768e9001cdba9f016d43e0&table=sc_cat_item" 
                       target="_blank" 
                       style="color:#721c24; font-weight:bold; text-decoration:underline;">
                       PromoMats and Medical Veeva Vault - Request Access / Remove Access - Internal - MyServices
                    </a>
                  </li>
                  <li>
                    <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=f020f7cc47768e9001cdba9f016d43e0&table=sc_cat_item" 
                       target="_blank" 
                       style="color:#721c24; font-weight:bold; text-decoration:underline;">
                       PromoMats and Medical Veeva Vault - Request Access / Remove Access - External - MyServices
                    </a>
                  </li>
                </ul>
              </div>
            `;
          }
        } catch (err) {
          console.error("Error:", err);
        }
      }
    
      // Asignar eventos
      document.getElementById("btnReactivate").addEventListener("click", () => handleAction("reactivate"));
      document.getElementById("btnInactivate").addEventListener("click", () => handleAction("inactivate"));
    </script>
</body>
</html>
