const http = require("node:http");
const fs = require("node:fs");
const puerto = 3000;

var arreglo_musica = new Array();

// Leemos todos los archivos dentro de la carpeta ./musica_fuvol
fs.readdir("./musica_fuvol", (err, files) => {
    if (err) {
        console.error("Error al leer la carpeta ./musica_fuvol:", err);
        return;
    }

    for (let i = 0; i < files.length; i++) {
        // Filtramos para asegurarnos de leer solo archivos .json
        if (files[i].endsWith(".json")) {
            fs.readFile("./musica_fuvol/" + files[i], "utf-8", (err, file) => {
                if (err) {
                    console.error("Error al leer el archivo " + files[i] + ":", err);
                    return;
                }
                arreglo_musica.push(JSON.parse(file));
                // Ordenamos por ID para que siempre salgan en orden aunque carguen asíncronamente
                arreglo_musica.sort((a, b) => a.id - b.id);
            });
        }
    }
});

const server = http.createServer((request, response) => {
    // Configuración de CORS
    response.setHeader("Access-Control-Allow-Origin", "*");
    response.setHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
    response.setHeader("Access-Control-Allow-Headers", "*");

    console.log("Ruta solicitada:", request.url);
    console.log("Método:", request.method);

    // Respuesta rápida para preflight requests de CORS en el navegador
    if (request.method === "OPTIONS") {
        response.statusCode = 204;
        response.end();
        return;
    }

    switch (request.method) {
        case "GET":
            if (request.url === "/") {
                // Devuelve la cantidad total de canciones cargadas en la API
                var objeto_cantidad = {
                    "cantidad_de_canciones": arreglo_musica.length
                };
                response.statusCode = 200;
                response.setHeader("Content-Type", "application/json; charset=utf-8");
                response.end(JSON.stringify(objeto_cantidad));
            } 
            else if (request.url === "/musica") {
                // Devuelve el arreglo completo con todas las canciones
                response.statusCode = 200;
                response.setHeader("Content-Type", "application/json; charset=utf-8");
                response.end(JSON.stringify(arreglo_musica));
            } 
            else if (request.url.startsWith("/musica/")) {
                // Extraemos el id desde la URL: ["", "musica", "id"]
                const id = parseInt(request.url.split("/")[2]);

                // Buscamos la canción que coincida con el ID
                const cancion = arreglo_musica.find(c => c.id === id);

                if (cancion) {
                    response.statusCode = 200;
                    response.setHeader("Content-Type", "application/json; charset=utf-8");
                    response.end(JSON.stringify(cancion));
                } else {
                    response.statusCode = 404;
                    response.setHeader("Content-Type", "application/json; charset=utf-8");
                    response.end(JSON.stringify({ error: "Todavía no contamos con esa canción" }));
                }
            } 
            else {
                // Ruta no encontrada
                response.statusCode = 404;
                response.setHeader("Content-Type", "application/json; charset=utf-8");
                response.end(JSON.stringify({ error: "Ruta no encontrada" }));
            }
            break;

        default:
            response.statusCode = 405;
            response.setHeader("Content-Type", "application/json; charset=utf-8");
            response.end(JSON.stringify({ error: "Método no permitido" }));
            break;
    }
});

server.listen(puerto, () => {
    console.log(`Servidor de música de fútbol escuchando en http://localhost:${puerto}`);
});