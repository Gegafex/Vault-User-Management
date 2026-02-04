<?php
session_start();

function authenticateVault() {
    $url = "https://bisbx-promomats.veevavault.com/api/v25.2/auth";

    // Igual que en Postman: form-data
    $credentials = include('api/credentials.php');
    $username = $credentials['username'];
    $password = $credentials['password'];


    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $credentials); // array → multipart/form-data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Opcional: headers básicos (cURL calcula boundary solo)
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: */*"
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("Connection error: " . $error);
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
        die("It was not possible to athenticate against Veeva Vault.");
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
    #loader {
      display: none; /* oculto por defecto */
      position: fixed; /* se queda fijo en pantalla */
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255,255,255,0.7); /* capa semitransparente */
      z-index: 9999; /* por encima de todo */
      justify-content: center;
      align-items: center;
    }
    #loader img {
      width: 120px; /* tamaño del spinner */
      height: 100px;
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
    <div id="loader">
        <img src="img/spinner.gif" alt="Loading..." />
    </div>

    <script>
        function showLoader() {
          const loader = document.getElementById('loader');
          loader.style.display = 'flex'; // aparece centrado
        }
        
        function hideLoader() {
          const loader = document.getElementById('loader');
          loader.style.display = 'none'; // vuelve a ocultarse
        }

    </script>

  <div class="login-box">
    <!-- Logo Boehringer Ingelheim -->
    <img src="img/boehringer_logo.png" alt="Boehringer Ingelheim">

    <!-- Título central -->
    <h1>Reactivate / Inactivate Account</h1>

    <!-- Logo Veeva Vault -->
    <img src="img/veeva_logo.png" alt="Veeva Vault">

    <!-- Formulario -->
    <form id="accountForm">
        <input type="email" id="emailInput" placeholder="Enter your email" required />
        <button type="button" id="btnReactivate" class="btn">Reactivate Account</button><br>
        <button type="button" id="btnInactivate" class="btn">Inactivate Account</button>
    </form>
      <div id="messageBox"></div>
    
      <!-- Contenedor para mensajes dinámicos -->
      <div id="messageBox"></div>
    </div>

    <!-- Script -->
    <script>
      const sessionId = "<?php echo $_SESSION['vault_sessionId']; ?>";
      async function handleAction(actionType) {
        const email = emailInput.value.trim();
        
        if (!email) return;
    
        try {
            showLoader();
          // 1. Retrieve User by email
          const response = await fetch("api/retrieveUserByEmail.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, sessionId })
          });
    
          const data = await response.json();
          
          if (response.ok && data.success) {
            const userId = data.userId;
    
            // 2. Según el botón elegido
            if (actionType === "reactivate") {
              const reactivateResp = await fetch("api/reactivateUser.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: userId, sessionId })
              });
    
              const reactivateData = await reactivateResp.json();
    
              if (reactivateResp.ok && reactivateData.success) {
                document.getElementById("messageBox").innerHTML = `
                  <div style="
                    background-color:#d4edda;
                    color:#155724;
                    border-radius:8px;
                    padding:12px;
                    margin-top:12px;">
                    Account Reactivated successfully, now you can login to 
                    <a href="https://login.veevavault.com" target="_blank" 
                       style="color:#155724; font-weight:bold; text-decoration:underline;">
                       bi-vault
                    </a>.
                  </div>
                `;
              } else if (userId) {
                  const errormsg = reactivateData.errors; //explorar cómo traerme el verdadero mensaje
                  document.getElementById("messageBox").innerHTML = `
                  <div style="
                    background-color:#f8d7da;
                    color:#856404;
                    border-radius:8px;
                    padding:12px;
                    text-align: left;
                    margin-top:12px;">
                     Your account cannot be Reactivated for one of the following reasons: <br>
                    <li>The account is inactive at domain level.</li>
                    <li>There is no license available.</li><br>
                    Please contact an administrator <a href="mailto:zzITM_SVaultCOE@boehringer-ingelheim.com">here</a>
                  </div>
                  </div>
                `;
              } else {
                // 3. Usuario no encontrado → mensaje rojo con bullets
                document.getElementById("messageBox").innerHTML = `
                  <div style="
                    background-color:#f8d7da;
                    color:#721c24;
                    border-radius:8px;
                    padding:12px;
                    text-align: left;
                    margin-top:12px;">
                    <p>User does not exist in the selected environment, please go the following MyServices form to request your account:</p>
                    <ul style="margin-left:20px;">
                      <li>
                        <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=f020f7cc47768e9001cdba9f016d43e0&table=sc_cat_item" 
                           target="_blank" 
                           style="color:#721c24; font-weight:bold; text-decoration:underline;">
                           PromoMats and Medical Veeva Vault - Request Access / Remove Access - Internal - MyServices
                        </a>
                      </li><br>
                      <li>
                        <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=1bcf3a5493881ed8fcb8bd0c5cba10f9&table=sc_cat_item" 
                           target="_blank" 
                           style="color:#721c24; font-weight:bold; text-decoration:underline;">
                           PromoMats and Medical Veeva Vault - Request Access / Remove Access - External - MyServices
                        </a>
                      </li>
                    </ul>
                  </div>
                `;
              }
              hideLoader();
            } else if (actionType === "inactivate") {
              const inactivateResp = await fetch("api/inactivateUser.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: userId, sessionId})
              });
    
              const inactivateData = await inactivateResp.json();
    
              if (inactivateResp.ok && inactivateData.success) {
                document.getElementById("messageBox").innerHTML = `
                  <div style="
                    background-color:#fff3cd;
                    color:#856404;
                    border-radius:8px;
                    padding:12px;
                    margin-top:12px;">
                    Account Inactivated successfully.
                  </div>
                `;
              } else if (userId) {
                  const errormsg = inactivateData.errors; //explorar cómo traerme el verdadero mensaje
                  document.getElementById("messageBox").innerHTML = `
                  <div style="
                    background-color:#f8d7da;
                    color:#856404;
                    border-radius:8px;
                    padding:12px;
                    margin-top:12px;">
                     Your account cannot be Inactivated, it seems to be already Inactive.
                  </div>
                `;
              } else {
              
                    // 3. Usuario no encontrado → mensaje rojo con bullets
                    document.getElementById("messageBox").innerHTML = `
                      <div style="
                        background-color:#f8d7da;
                        color:#721c24;
                        border-radius:8px;
                        padding:12px;
                        text-align: left;
                        margin-top:12px;">
                        <p>User does not exist in the selected environment, please go the following MyServices form to request your account:</p>
                        <ul style="margin-left:20px;">
                          <li>
                            <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=f020f7cc47768e9001cdba9f016d43e0&table=sc_cat_item" 
                               target="_blank" 
                               style="color:#721c24; font-weight:bold; text-decoration:underline;">
                               PromoMats and Medical Veeva Vault - Request Access / Remove Access - Internal - MyServices
                            </a>
                          </li><br>
                          <li>
                            <a href="https://boehringer.service-now.com/esc?id=sc_cat_item&sys_id=1bcf3a5493881ed8fcb8bd0c5cba10f9&table=sc_cat_item" 
                               target="_blank" 
                               style="color:#721c24; font-weight:bold; text-decoration:underline;">
                               PromoMats and Medical Veeva Vault - Request Access / Remove Access - External - MyServices
                            </a>
                          </li>
                        </ul>
                      </div>
                    `;
                }
                hideLoader();
            }
        }
       } catch (err) {
          //hideLoader();
          console.error("Error:", err);
        }
      }
     
      // Asignar eventos
      document.getElementById("btnReactivate").addEventListener("click", () => handleAction("reactivate"));
      document.getElementById("btnInactivate").addEventListener("click", () => handleAction("inactivate"));
      
    </script>
</body>
</html>
