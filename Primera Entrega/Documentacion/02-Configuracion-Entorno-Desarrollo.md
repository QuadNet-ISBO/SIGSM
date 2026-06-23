# Configuración del Entorno de Desarrollo

Esta es la guía para levantar el proyecto en otra máquina, ya sea de un integrante nuevo o del docente que quiera probarlo. Sirve tanto para el prototipo (que no necesita nada de esto, ver el README) como para el avance funcional en PHP que tenemos en la carpeta `Modulo2`.

## Lo que hace falta instalar

- **XAMPP**, versión 8.2.x — trae Apache, PHP 8.2 y MariaDB en un solo instalador. Se descarga de [apachefriends.org](https://www.apachefriends.org/download.html). Usamos esta versión porque es la más reciente con PHP 8.2 al momento de armar esto; si en el futuro se actualiza, solo hay que cuidar que la versión de PHP no baje de 8.0.
- **Git**, cualquier versión reciente.
- **Visual Studio Code**.
- Un navegador (Chrome, Edge o Firefox) con las DevTools, para poder ver el prototipo en modo mobile.

## Instalar XAMPP

Se descarga el instalador y se corre normal. En algún punto va a pedir permisos de administrador o que se desactive momentáneamente el antivirus — es esperado, porque necesita escribir en `C:\xampp`. Se deja todo tildado por default (Apache, MariaDB, PHP, phpMyAdmin) y se instala en la ruta default.

Una vez instalado, se abre el Panel de Control de XAMPP y se le da Start a Apache y a MySQL. Tienen que quedar los dos en verde.

## Poner el proyecto donde Apache lo pueda servir

Apache busca los archivos en `C:\xampp\htdocs`, así que el proyecto tiene que estar ahí adentro (o enlazado). Lo más directo es clonar el repo justo en esa carpeta:

```bash
cd C:/xampp/htdocs
git clone https://github.com/QuadNet-ISBO/SIGSM.git
```

Si ya tenés el repo clonado en otro lado (por ejemplo dentro de OneDrive, como en este caso) y no querés duplicarlo, se puede hacer un symlink en vez de clonar de nuevo:

```powershell
New-Item -ItemType SymbolicLink -Path "C:\xampp\htdocs\SIGSM" -Target "C:\ruta\a\tu\SIGSM-WIP"
```

Con cualquiera de las dos opciones el proyecto va a quedar accesible en `http://localhost/SIGSM/`.

## Importar las bases de datos

Con Apache y MySQL corriendo, se entra a `http://localhost/phpmyadmin`, se va a la pestaña Importar y se selecciona `Modulo1/BD/bd_modulo1.sql`. Se repite lo mismo con `Modulo2/BD/bd_modulo2.sql`. Los dos scripts ya traen `CREATE DATABASE IF NOT EXISTS`, así que no hace falta crear nada a mano antes.

Después de importar deberían aparecer las bases `bd_modulo1` y `bd_modulo2`, y en el caso de `bd_modulo2` las tablas de catálogo (roles, tipos de vehículo, etc.) ya vienen con datos de ejemplo cargados.

## La conexión PHP-MySQL

Los `conexion.php` de cada módulo se conectan con el usuario `root` sin contraseña, que es la config por defecto de XAMPP:

```php
$conexion = new mysqli("localhost", "root", "", "bd_modulo2");
```

Si tu MySQL tiene contraseña propia hay que cambiar ese tercer parámetro vacío.

## Cómo saber que quedó todo bien

Abriendo `http://localhost/SIGSM/Modulo2/traslados.php` debería aparecer el listado de traslados de prueba, sin ningún error de PHP en la pantalla. El prototipo, por su parte, no necesita nada de XAMPP: se puede abrir directo desde `Primera Entrega/proto/index.html`.

## VS Code

Las extensiones que usamos son:

- **PHP Intelephense** — autocompletado y errores de PHP.
- **SQLTools** (+ su driver de MySQL) — para mirar la base sin salir del editor.
- **Live Server** — para servir el prototipo con recarga automática.
- **GitLens** — para ver quién tocó qué línea y cuándo.
- **Prettier** — para que el formato de HTML/CSS/JS no varíe de uno a otro.

Se pueden instalar todas de una desde la terminal de VS Code:

```bash
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension mtxr.sqltools
code --install-extension mtxr.sqltools-driver-mysql
code --install-extension ritwickdey.LiveServer
code --install-extension eamodio.gitlens
code --install-extension esbenp.prettier-vscode
```

Para que el resto del equipo termine con las mismas extensiones, VS Code permite sacar la lista de lo que tenés instalado:

```bash
code --list-extensions > vscode-extensions.txt
```

y después cualquiera puede instalarlas todas a partir de ese archivo:

```bash
# Linux/macOS
cat vscode-extensions.txt | xargs -L 1 code --install-extension

# PowerShell
Get-Content vscode-extensions.txt | ForEach-Object { code --install-extension $_ }
```
