# SIGSM — Instalación local con XAMPP

Guía para ejecutar SIGSM en Windows mediante XAMPP. Esta instalación no requiere Docker ni una máquina virtual.

## Requisitos

- XAMPP con Apache, PHP y MySQL/MariaDB.
- PHP 8.3 recomendado, con las extensiones `pdo_mysql`, `mbstring` y `fileinfo` habilitadas.
- Un navegador web.

## 1. Copiar el proyecto

1. Descomprimir el ZIP de SIGSM.
2. Copiar la carpeta `SIGSM` a `C:\xampp\htdocs\`.
3. Comprobar que existan estos archivos:

C:\xampp\htdocs\SIGSM\Frontend\login.html
C:\xampp\htdocs\SIGSM\Backend\database\bd.sql

**Importante:** conservar el nombre de la carpeta `SIGSM` para utilizar la dirección de acceso de esta guía.

## 2. Iniciar XAMPP

1. Abrir XAMPP Control Panel.
2. Presionar **Start** en Apache y MySQL.
3. Abrir phpMyAdmin en el navegador.

Si algún servicio no inicia, comprobar que los puertos 80 (Apache) y 3306 (MySQL) no estén ocupados.

## 3. Importar la base de datos

1. En phpMyAdmin, abrir la pestaña **Importar**.
2. Seleccionar el archivo:

C:\xampp\htdocs\SIGSM\Backend\database\bd.sql

3. Presionar **Importar** o **Continuar**.
4. Comprobar que exista la base de datos `sigsm` con sus tablas.

El archivo SQL contiene instrucciones para crear y seleccionar la base de datos. Importarlo en una instalación nueva. Si ya existen datos, hacer un respaldo antes de repetir la importación.

## 4. Comprobar la conexión

El archivo `Backend/config/database.php` utiliza estos valores predeterminados para XAMPP:

- Servidor: `localhost`
- Puerto: `3306`
- Base de datos: `sigsm`
- Usuario: `root`
- Contraseña: vacía

No hace falta crear un archivo `.env` para esta instalación. Si MySQL tiene otra configuración, habrá que adaptar los valores de conexión.

## 5. Abrir SIGSM

Ingresar desde el navegador:

http://localhost/SIGSM/Frontend/index.html

El SQL incluye un usuario inicial de pruebas:

- Usuario: `admin1`
- Contraseña: `admin123`

**Importante:** cambiar la contraseña inicial antes de usar el sistema fuera de un entorno local de pruebas.

## 6. Verificar el funcionamiento

Después de iniciar sesión, comprobar:

- Panel principal y administración.
- Funcionarios, vehículos y traslados.
- Categorías, subcategorías y encuestas.
- Carga y visualización de documentos PDF.

También conviene reiniciar Apache y MySQL y comprobar que la información siga disponible.

## Problemas frecuentes

- **Error 404:** revisar la carpeta `htdocs\SIGSM` y la dirección `/SIGSM/`.
- **Error de base de datos:** comprobar que MySQL esté iniciado, que exista la base `sigsm` y que usuario y contraseña sean correctos.
- **Error 500:** revisar los registros de Apache/PHP y las extensiones de PHP.
- **Error al importar SQL:** comprobar si la base ya contiene tablas o datos.
- **No se pueden subir PDF:** revisar los permisos de `uploads/documentos/` y los límites de PHP.

## Nota de seguridad

El proyecto incluye `uploads/.htaccess` para proteger documentos. No eliminarlo. Apache debe permitir la lectura de archivos `.htaccess` mediante la configuración correspondiente (`AllowOverride All`).

La configuración de seguridad de Apache incluida para Docker no se aplica automáticamente a XAMPP. Esta guía está pensada para uso local; no publicar XAMPP en Internet sin proteger los directorios internos y archivos de configuración.

---

SIGSM · Instalación local con XAMPP · Paquete de referencia: `SIGSM.zip`
