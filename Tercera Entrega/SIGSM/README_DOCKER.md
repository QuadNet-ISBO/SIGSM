# S.I.G.S.M. — Manual de instalación y funcionalidades


**Administración de Sistemas Operativos · Equipo QuadNet · Proyecto de Egreso 2026**

Documentación técnica del servidor **CentOS Stream 10**, la configuración de red, el acceso remoto SSH, la administración mediante scripts Bash y el despliegue de SIGSM con Docker Compose.


**Alcance**: este manual corresponde a la máquina virtual preparada y distribuida como archivo `.ova`. No describe una instalación desde cero de CentOS o Docker. Las credenciales de Linux y de la aplicación web son independientes.


## 1\. Especificaciones del servidor, red estática y SSH


* **Sistema operativo:** CentOS Stream 10, instalación mínima con interfaz de línea de comandos (CLI).
* **Virtualización:** Oracle VirtualBox; distribución mediante archivo `.ova`.
* **Interfaz de red:** `enp0s3`, configurada en modo adaptador puente.
* **Dirección IPv4 prevista:** `192.168.1.10/24`.
* **Puerta de enlace prevista:** `192.168.1.1`.
* **Acceso remoto:** OpenSSH, puerto `22/tcp`.
* **Aplicación web:** Apache y PHP 8.3 en Docker, publicada en el puerto `8080/tcp`.
* **Base de datos:** MySQL 8.4, accesible para la aplicación mediante la red interna de Docker.
* **Usuario Linux para consola y SSH:** `admin`.
* **Contraseña de Linux:** utilizar la proporcionada por el responsable de la entrega; no se publica en este documento.
* **Usuario y contraseña de SIGSM:** corresponden a las cuentas registradas en la base de datos de la aplicación, **no** necesariamente a las credenciales de Linux.


**Dirección de acceso a SIGSM:**


http://192.168.1.10:8080/SIGSM3/Frontend/index.html


La IP indicada depende de la red donde se importe la VM. Si el equipo anfitrión utiliza otra subred o ya existe un dispositivo con `192.168.1.10`, habrá que adaptar la configuración de red y utilizar la nueva dirección.


### Comprobación de red y SSH

En la terminal de CentOS:


ip -br -4 addr
sudo systemctl status sshd
sudo firewall-cmd --list-services


Desde PowerShell en Windows, una vez que la VM sea accesible:
ssh admin@192.168.1.10


Si la interfaz no muestra una dirección IPv4, revisar primero la configuración del adaptador de VirtualBox y el perfil de NetworkManager. No se debe asumir que la IP se aplicó correctamente solo porque el perfil figure como conectado.


## 2\. Manual de instalación y despliegue


### 2.1. Importar la máquina virtual


1. Abrir **VirtualBox** y seleccionar **Archivo → Importar servicio virtualizado**.
2. Seleccionar el archivo `.ova` entregado e importar la máquina.
3. Comprobar que el adaptador de red esté configurado de acuerdo con la red del equipo anfitrión; el entorno fue preparado para utilizar **adaptador puente**.
4. Iniciar CentOS Stream 10 e ingresar con el usuario Linux `admin` y la contraseña facilitada por el responsable.
5. Verificar la dirección IP con `ip -br -4 addr`.


### 2.2. Iniciar SIGSM con el instalador preparado


**Ejecutar**: sudo /opt/gestion-sistema/instalar-sigsm.sh


En la versión preparada de la VM, este script **inicia los servicios existentes de Docker Compose y verifica su estado**, incluida la disponibilidad de MySQL. Está pensado para desplegar SIGSM **sin reconstruir innecesariamente la imagen ni eliminar los datos persistentes**.

El código del proyecto se encuentra en:

/opt/proyecto

### 2.3. Comprobar el estado de los contenedores

cd /opt/proyecto
sudo docker compose ps

Se esperan los servicios:

* **`proyecto-web-1`:** servidor Apache y PHP para SIGSM.
* **`proyecto-mysql-1`:** base de datos MySQL 8.4, con comprobación de salud (`healthy`).


**Para consultar registros de ejecución:**

sudo docker compose logs --tail=50

**Para detener los servicios sin eliminar los datos:**

sudo docker compose stop

Para volver a iniciarlos, ejecutar nuevamente el instalador o usar `sudo docker compose start` si los contenedores ya existen.

**Precaución**: no utilizar `docker compose down -v`. La opción `-v` elimina los volúmenes asociados y puede provocar la pérdida de la base de datos y los documentos cargados. La reconstrucción con `docker compose up -d --build` no es necesaria para el uso normal del OVA preparado.


### 2.4. Acceso a la aplicación

Abrir desde un navegador del equipo anfitrión:

http://192.168.1.10:8080/SIGSM3/Frontend/login.html


Iniciar sesión con una **cuenta de SIGSM** válida (**user**: `admin1` y **password**: `admin123`).


## 3\. Funcionalidades del sistema modular de administración

Los scripts de administración se encuentran en `/opt/gestion-sistema`. El menú principal se inicia con:

sudo /opt/gestion-sistema/operador.sh


### Módulos disponibles

1. **Usuarios y grupos:** administración de cuentas Linux, pertenencia a grupos y permisos, mediante los scripts correspondientes.
2. **Respaldos:** operaciones de respaldo mediante `respaldos.sh`. El script contiene referencias a una exportación SQL de MySQL; las rutas de destino, la automatización y la restauración deben comprobarse antes de utilizarlas como procedimiento de recuperación.
3. **Redes:** consulta de interfaces, direccionamiento, rutas, puertos y conectividad mediante `redes.sh`.
4. **Servicios:** revisión y administración de servicios del sistema mediante `servicios.sh`.
5. **Firewall:** consulta y administración de reglas con `firewalld` mediante `firewall.sh`.
6. **Logs:** consulta de registros del sistema mediante `logs.sh` y herramientas como `journalctl`.


**Nota sobre los respaldos:** durante la preparación de la entrega se eliminaron copias antiguas y el archivo `/opt/proyecto/Backend/database/ultimo\_respaldo.sql`. El script `respaldos.sh` todavía puede generar ese archivo si se ejecuta. No se debe afirmar que existe una copia de seguridad actualizada sin comprobarla.


### Verificación funcional de SIGSM

Tras iniciar sesión con una cuenta autorizada, comprobar que cargan los módulos correspondientes al rol:

* Usuarios y funcionarios.
* Vehículos.
* Traslados y seguimiento de estados.
* Categorías y subcategorías.
* Documentos y encuestas.
* Portal público de pacientes.


## 4\. Funcionamiento HTTP, contenedores y seguridad


### 4.1. Arquitectura de contenedores

La aplicación se ejecuta con **Docker Compose** y dos servicios principales:

* **Web:** Apache HTTP Server con PHP 8.3, accesible por el puerto `8080` del servidor.
* **MySQL:** versión 8.4; la aplicación se conecta al servicio `mysql` dentro de la red de Docker.


El archivo `compose.yml` define los servicios, las variables de entorno y los volúmenes persistentes. El `Dockerfile` prepara Apache y PHP; `docker/apache-sigsm.conf` incorpora reglas de configuración del servidor, y `docker/uploads.ini` establece límites de subida de archivos.


Los datos de MySQL y los documentos cargados se conservan en volúmenes Docker. Por eso, **detener los contenedores no equivale a borrar la información**.


### 4.2. Protección de documentos y archivos internos

El proyecto incluye `uploads/.htaccess`, que impide el acceso HTTP directo a los documentos. Los PDF se entregan mediante endpoints PHP que comprueban las condiciones de acceso correspondientes.

En la imagen Docker preparada, Apache permite aplicar las reglas `.htaccess` y cuenta con configuración específica para las rutas internas. **Esta configuración de Apache no se transfiere automáticamente a una instalación independiente de XAMPP.**


**QuadNet · SIGSM · Proyecto de Egreso 2026**  
**Entorno de referencia:** CentOS Stream 10 · VirtualBox · Docker Compose · Apache/PHP 8.3 · MySQL 8.4.
