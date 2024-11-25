CREATE TABLE pais
(
    id     int AUTO_INCREMENT PRIMARY KEY,
    nombre nvarchar(50) UNIQUE NOT NULL
);

CREATE TABLE ciudad
(
    id      int AUTO_INCREMENT PRIMARY KEY,
    nombre  nvarchar(50) NOT NULL,
    id_pais int NOT NULL,
    CONSTRAINT unique_ciudad_pais UNIQUE (nombre, id_pais),
    CONSTRAINT ciudad_fk FOREIGN KEY (id_pais) REFERENCES pais (id)
);

CREATE TABLE sexo
(
    id     int AUTO_INCREMENT PRIMARY KEY,
    nombre nvarchar(30) UNIQUE
);

CREATE TABLE usuario
(
    id              int AUTO_INCREMENT PRIMARY KEY,
    nombre          nvarchar(50) NOT NULL,
    apellido        nvarchar(50) NOT NULL,
    username        nvarchar(50) UNIQUE NOT NULL,
    email           nvarchar(255) UNIQUE NOT NULL,
    password        nvarchar(255) NOT NULL,
    anio_nacimiento int NOT NULL,
    foto_perfil     nvarchar(255),
    id_sexo         int NOT NULL,
    id_ciudad       int NOT NULL,
    token           nvarchar(255),
    fecha_registro  timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT usuario_sexo_fk FOREIGN KEY (id_sexo) REFERENCES sexo (id),
    CONSTRAINT usuario_ciudad_fk FOREIGN KEY (id_ciudad) REFERENCES ciudad (id)
);


CREATE INDEX idx_ciudad_pais ON ciudad (id_pais);
CREATE INDEX idx_usuario_ciudad ON usuario (id_ciudad);
CREATE INDEX idx_usuario_sexo ON usuario (id_sexo);

CREATE TABLE jugador
(
    id         int PRIMARY KEY,
    verificado BOOLEAN,
    CONSTRAINT jugador_fk FOREIGN KEY (id) REFERENCES usuario (id)
);

CREATE TABLE administrador
(
    id int PRIMARY KEY,
    CONSTRAINT administrador_fk FOREIGN KEY (id) REFERENCES usuario (id)
);

CREATE TABLE editor
(
    id int PRIMARY KEY,
    CONSTRAINT editor_fk FOREIGN KEY (id) REFERENCES usuario (id)
) INSERT INTO sexo(nombre) VALUES ('Masculino'), ('Femenino'), ('Prefiero no cargarlo');

INSERT INTO pais(nombre)
VALUES ('Argentina');

INSERT INTO ciudad(nombre, id_pais)
VALUES ('Rafael Castillo', 1),
       ('Isidro Casanova', 1);


-- INSERT INTO usuario(nombre, apellido, username, email, password, anio_nacimiento, id_sexo, id_ciudad)
-- VALUES
-- ('Lorenzo', 'Noceda', 'loren19', 'loren@editor.com', '12345', '2001', 1, 1),
-- ('Tomas', 'Nania', 'tomi10', 'tomi@editor.com', '12345', '2003', 1, 2),
-- ('Brian', 'Hidalgo', 'brian7', 'brian@admin.com', '12345', '2002', 3, 1),
-- ('Gonzalo', 'Ramos', 'gonza5', 'gonza@admin.com', '12345', '2003', 1, 2);
-- Registrarse y obtener el propio

INSERT INTO editor
VALUES (1),
       (2);

INSERT INTO administrador
VALUES (3),
       (4);


INSERT INTO pais (nombre)
VALUES ('Brasil'),
       ('Chile');

INSERT INTO ciudad (nombre, id_pais)
VALUES ('Buenos Aires', 1),
       ('Córdoba', 1),
       ('Rosario', 1),
       ('La Plata', 1),
       ('Mendoza', 1),
       ('Brasilia', 2),
       ('São Paulo', 2),
       ('Río de Janeiro', 2),
       ('Santiago', 3),
       ('Valparaíso', 3);


CREATE TABLE estado
(
    id          int AUTO_INCREMENT PRIMARY KEY,
    descripcion nvarchar(50) UNIQUE NOT NULL
);

INSERT INTO estado (descripcion)
VALUES ('Pendiente'),
       ('Aprobada'),
       ('Rechazada'),
       ('Reportada'),
       ('Desactivada');

CREATE TABLE categoria
(
    id          int AUTO_INCREMENT PRIMARY KEY,
    descripcion nvarchar(255)NOT NULL,
    color       nvarchar(10)
);


INSERT INTO categoria (descripcion, color)
VALUES ('Ciencia', '#4CAF50'),
       ('Historia', '#FFC107'),
       ('Geografía', '#03A9F4'),
       ('Deportes', '#FF5722'),
       ('Entretenimiento', '#9C27B0'),
       ('Arte', '#E91E63'),
       ('Literatura', '#673AB7'),
       ('Cine', '#795548');

CREATE TABLE pregunta
(
    id           int AUTO_INCREMENT PRIMARY KEY,
    texto        nvarchar(255)NOT NULL,
    id_categoria int NOT NULL,
    id_estado    int NOT NULL,
    CONSTRAINT categoria_fk
        FOREIGN KEY (id_categoria) REFERENCES categoria (id),
    CONSTRAINT estado_fk
        FOREIGN KEY (id_estado) REFERENCES estado (id)
);


INSERT INTO pregunta (texto, id_categoria, id_estado)
VALUES ('¿Cuál es el planeta más cercano al sol?', 1, 1),                   -- Ciencia
       ('¿Quién fue el primer presidente de los Estados Unidos?', 2, 1),    -- Historia
       ('¿Cuál es la capital de Francia?', 3, 1),                           -- Geografía
       ('¿Cuántos jugadores tiene un equipo de fútbol en el campo?', 4, 1), -- Deportes
       ('¿Cuál es el nombre del ratón amigo de Mickey Mouse?', 5, 1),       -- Entretenimiento
       ('¿Quién pintó la Mona Lisa?', 6, 1),                                -- Arte
       ('¿Quién escribió "Don Quijote de la Mancha"?', 7, 1),               -- Literatura
       ('¿Cuál fue la primera película de Star Wars en ser lanzada?', 8, 1); -- Cine


CREATE TABLE respuesta
(
    id          int AUTO_INCREMENT PRIMARY KEY,
    texto       nvarchar(255) NOT NULL,
    id_pregunta int     NOT NULL,
    esCorrecta  tinyint NOT NULL,
    CONSTRAINT unique_pregunta_respuesta
        UNIQUE (texto, id_pregunta),
    CONSTRAINT pregunta_fk
        FOREIGN KEY (id_pregunta) REFERENCES pregunta (id)
);


-- Respuestas para "¿Cuál es el planeta más cercano al sol?" (Ciencia)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('Mercurio', 1, 1),
       ('Venus', 1, 0),
       ('Tierra', 1, 0),
       ('Marte', 1, 0);


-- Respuestas para "¿Quién fue el primer presidente de los Estados Unidos?" (Historia)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('George Washington', 2, 1),
       ('Abraham Lincoln', 2, 0),
       ('John Adams', 2, 0),
       ('Thomas Jefferson', 2, 0);


-- Respuestas para "¿Cuál es la capital de Francia?" (Geografía)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('París', 3, 1),
       ('Madrid', 3, 0),
       ('Berlín', 3, 0),
       ('Roma', 3, 0);


-- Respuestas para "¿Cuántos jugadores tiene un equipo de fútbol en el campo?" (Deportes)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('11', 4, 1),
       ('9', 4, 0),
       ('10', 4, 0),
       ('12', 4, 0);


-- Respuestas para "¿Cuál es el nombre del ratón amigo de Mickey Mouse?" (Entretenimiento)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('Minnie', 5, 0),
       ('Donald', 5, 0),
       ('Goofy', 5, 0),
       ('Mickey', 5, 1);


-- Respuestas para "¿Quién pintó la Mona Lisa?" (Arte)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('Leonardo da Vinci', 6, 1),
       ('Pablo Picasso', 6, 0),
       ('Vincent van Gogh', 6, 0),
       ('Claude Monet', 6, 0);


-- Respuestas para "¿Quién escribió 'Don Quijote de la Mancha'?" (Literatura)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('Miguel de Cervantes', 7, 1),
       ('Gabriel García Márquez', 7, 0),
       ('Mario Vargas Llosa', 7, 0),
       ('Jorge Luis Borges', 7, 0);


-- Respuestas para "¿Cuál fue la primera película de Star Wars en ser lanzada?" (Cine)
INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
VALUES ('Episodio IV: Una Nueva Esperanza', 8, 1),
       ('Episodio I: La Amenaza Fantasma', 8, 0),
       ('Episodio V: El Imperio Contraataca', 8, 0),
       ('Episodio VI: El Retorno del Jedi', 8, 0);


CREATE TABLE usuario_pregunta
(
    usuario_id               INT     NOT NULL,
    pregunta_id              INT     NOT NULL,
    respondida_correctamente TINYINT NOT NULL,
    PRIMARY KEY (usuario_id, pregunta_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario (id),
    FOREIGN KEY (pregunta_id) REFERENCES pregunta (id)
);

-- 02/11/24
CREATE TABLE partida
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    jugador_id INT NOT NULL,
    puntaje    INT DEFAULT NULL,
    FOREIGN KEY (jugador_id) REFERENCES jugador (id)
);

-- 04/11/24
ALTER TABLE partida
    ADD COLUMN fecha_jugada TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 07/11/34

ALTER TABLE usuario
DROP FOREIGN KEY usuario_ciudad_fk;
ALTER TABLE usuario
    DROP COLUMN id_ciudad;
DROP TABLE ciudad;
ALTER TABLE usuario
    ADD COLUMN latitud DECIMAL(10, 8) NULL,
    ADD COLUMN longitud DECIMAL(11, 8) NULL;

-- 10/11/24
ALTER TABLE estado ADD COLUMN color VARCHAR(7);
UPDATE estado SET color = '#28a745' WHERE descripcion = 'Aprobada';
UPDATE estado SET color = '#6c757d' WHERE descripcion = 'Desactivada';
UPDATE estado SET color = '#ffc107' WHERE descripcion = 'Pendiente';
UPDATE estado SET color = '#dc3545' WHERE descripcion = 'Rechazada';
UPDATE estado SET color = '#17a2b8' WHERE descripcion = 'Reportada';

CREATE TABLE reporte (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         texto TEXT NOT NULL,
                         id_usuario INT NOT NULL,
                         id_pregunta INT NOT NULL,
                         FOREIGN KEY (id_usuario) REFERENCES usuario(id),
                         FOREIGN KEY (id_pregunta) REFERENCES pregunta(id)
);

ALTER TABLE usuario
    ADD COLUMN cantidad_respondidas INT DEFAULT 0,
    ADD COLUMN cantidad_acertadas INT DEFAULT 0;

ALTER TABLE pregunta
    ADD COLUMN cantidad_respondidas INT DEFAULT 0,
    ADD COLUMN cantidad_acertadas INT DEFAULT 0;

ALTER TABLE usuario ADD COLUMN verificado TINYINT(1) DEFAULT 0;

UPDATE usuario u
    JOIN jugador j ON u.id = j.id
SET u.verificado = j.verificado
WHERE j.verificado IS NOT NULL;

ALTER TABLE jugador DROP COLUMN verificado;

-- musica juego
ALTER TABLE usuario ADD musica BOOLEAN DEFAULT FALSE;











