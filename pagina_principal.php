<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagina Principal</title>
    
<!--/////////////////////


este estilo es para poder mostrar lo que tenia en mente
>:P

meh :/

///////////////////////-->

    <style> 
        body {
            margin: 0;
            display: flex;
            font-family: Arial, sans-serif;
            background: #f3f4f8;
        }

        .sidebar {
            width: 210px;
            height: 100vh;
            background: #eef2ff;
            padding: 25px;
            position: fixed;
            display: flex;
            flex-direction: column;
            border-radius: 0 20px 20px 0;
            box-shadow: 4px 0 12px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            margin: 0;
            color: #333;
            font-size: 22px;
            text-align: center;
            margin-bottom: 5px;
        }

        .sidebar .usuario {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
        }

        .menu-links a {
            display: block;
            text-decoration: none;
            background: white;
            padding: 12px;
            border-radius: 12px;
            color: #555;
            font-size: 16px;
            margin-bottom: 12px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.09);
            transition: .25s;
            cursor: pointer;
        }

        .menu-links a:hover {
            background: #dce2ff;
            transform: translateX(5px);
        }

        .logout-btn {
            margin-top: auto;
            background: #ff5b5b;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(255,0,0,0.2);
            transition: .25s;
        }

        .logout-btn:hover {
            background: #e64a4a;
        }

        .contenido {
            margin-left: 260px;
            padding: 35px;
            width: calc(100% - 260px);
        }
    </style>

</head>
<body>

    <aside class="sidebar">
        <h2 id="tituloMenu">Pagina principal</h2>
        <br>



        <div class="menu-links" id="menuLinks">

            <a onclick="abrirMenu()">Menu2</a>
            <a href="nose.php" target="panel">boton1</a>
            <a href="nose.php" target="panel">boton2</a>

        </div>

        <form id="logoutBox" action="" method="POST">
            <button type="submit" class="logout-btn">Cerrar sesión</button>
        </form>
    </aside>

    <div class="contenido">
        <iframe name="panel" style="width:100%; height:90vh; border:none; border-radius:18px;
            box-shadow:0 5px 20px rgba(0,0,0,0.15); background:white;"></iframe>
    </div>


<script>
function abrirMenu() {
    document.getElementById("tituloMenu").innerText = "Menú de Productos";

    document.getElementById("logoutBox").style.display = "none";

    document.getElementById("menuLinks").innerHTML = `
        <a href="nose.php" target="panel">Ejemplo1</a>
        <a href="nose.php" target="panel">Ejemplo2</a>
        <a href="nose.php" target="panel">Ejemplo3</a>
        <a href="nose.php" target="panel">Ejemplo4</a>
        <a onclick="volverMenu()" style="background:#ffdcdc;">← Volver</a>
    `;
}

function volverMenu() {
    document.getElementById("tituloMenu").innerText = "Pagina principal";

    document.getElementById("logoutBox").style.display = "block";

    document.getElementById("menuLinks").innerHTML = `
        <a onclick="abrirMenu()">Menu2</a>
        <a href="nose.php" target="panel">boton1</a>
        <a href="admins.php" target="panel">boton2</a>
    `;
}
</script>

</body>
</html>
