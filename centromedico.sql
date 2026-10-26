-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-10-2026 a las 05:34:01
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `centromedico`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorios`
--

CREATE TABLE `consultorios` (
  `idConsultorio` int(11) NOT NULL,
  `conNombre` char(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `consultorios`
--

INSERT INTO `consultorios` (`idConsultorio`, `conNombre`) VALUES
(2, 'consultorio 2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicamentos`
--

CREATE TABLE `medicamentos` (
  `idMedicamento` int(11) NOT NULL,
  `medNombre` varchar(100) NOT NULL,
  `medImporte` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicamentos`
--

INSERT INTO `medicamentos` (`idMedicamento`, `medNombre`, `medImporte`) VALUES
(1, 'acetomifen', 6000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicos`
--

CREATE TABLE `medicos` (
  `idMedico` int(11) NOT NULL,
  `medIdentificacion` char(15) NOT NULL,
  `medNombres` varchar(50) NOT NULL,
  `medApellidos` varchar(50) NOT NULL,
  `medEspecialidad` varchar(50) NOT NULL,
  `medTelefono` char(15) NOT NULL,
  `medCorreo` varchar(50) NOT NULL,
  `usuarios_idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `idPaciente` int(11) NOT NULL,
  `pacIdentificacion` char(15) NOT NULL,
  `pacNombres` varchar(50) NOT NULL,
  `pacApellidos` varchar(50) NOT NULL,
  `pacFechaNacimiento` date NOT NULL,
  `pacSexo` varchar(20) NOT NULL,
  `usuarios_idUsuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`idPaciente`, `pacIdentificacion`, `pacNombres`, `pacApellidos`, `pacFechaNacimiento`, `pacSexo`, `usuarios_idUsuario`) VALUES
(1, 'z', 'z', 'z', '2026-10-13', 'Femenino', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL,
  `usuLogin` char(15) NOT NULL,
  `usuPassword` varchar(60) NOT NULL,
  `usuEstado` varchar(20) NOT NULL,
  `usuRol` text NOT NULL,
  `roles_id_rol` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  ADD PRIMARY KEY (`idConsultorio`);

--
-- Indices de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`idMedicamento`);

--
-- Indices de la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`idMedico`),
  ADD UNIQUE KEY `medIdentificacion` (`medIdentificacion`),
  ADD KEY `usuarios_idUsuario` (`usuarios_idUsuario`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`idPaciente`),
  ADD UNIQUE KEY `pacIdentificacion` (`pacIdentificacion`),
  ADD KEY `usuarios_idUsuario` (`usuarios_idUsuario`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `usuLogin` (`usuLogin`),
  ADD KEY `roles_id_rol` (`roles_id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `idConsultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `idMedicamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `medicos`
--
ALTER TABLE `medicos`
  MODIFY `idMedico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `idPaciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `medicos_ibfk_1` FOREIGN KEY (`usuarios_idUsuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`usuarios_idUsuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`roles_id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
