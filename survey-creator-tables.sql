-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 10, 2018 at 10:57 AM
-- Server version: 5.5.59-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `surveybuilder`
--

-- --------------------------------------------------------

--
-- Table structure for table `answerOptions`
--

CREATE TABLE IF NOT EXISTS `answerOptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionId` int(11) NOT NULL,
  `answer` int(11) NOT NULL,
  `createdDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question.id` (`questionId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `responseId` int(11) NOT NULL,
  `answerOptionId` int(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `createdDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `response.id` (`responseId`),
  KEY `answerId` (`answerOptionId`),
  KEY `responseId` (`responseId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `questionTypes`
--

CREATE TABLE IF NOT EXISTS `questionTypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typeName` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `questionTypes`
--

INSERT INTO `questionTypes` (`id`, `typeName`) VALUES
(1, 'Radio'),
(2, 'Checkbox'),
(3, 'Text');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surveyId` int(11) NOT NULL,
  `questionTypeId` int(11) NOT NULL,
  `questionText` varchar(512) NOT NULL,
  `comment` text,
  `createdDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey.id` (`surveyId`),
  KEY `questionTypeId` (`questionTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE IF NOT EXISTS `responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usersId` int(11) NOT NULL,
  `surveysId` int(11) NOT NULL,
  `submittedDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users.id` (`usersId`,`surveysId`),
  KEY `surveys.id` (`surveysId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE IF NOT EXISTS `surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `userId` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `createdDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user.id` (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=74 ;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `userId`, `startDate`, `endDate`, `createdDate`) VALUES
(69, 'trying again', 'Something something', 1, '2018-03-10', '2018-03-29', '2018-03-09'),
(70, 'Foo ', 'Donec rutrum congue leo eget malesuada. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n\nCurabitur non nulla sit amet nisl tempus convallis quis ac lectus. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla porttitor accumsan tincidunt. Quisque velit nisi, pretium ut lacinia in, elementum id enim.', 1, '0000-00-00', '0000-00-00', '2018-03-09'),
(71, 'Foo. Again.', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Vivamus suscipit tortor eget felis porttitor volutpat.\n\nNulla porttitor accumsan tincidunt. Nulla porttitor accumsan tincidunt. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem.', 1, '2018-03-10', '2018-04-12', '2018-03-09'),
(73, 'Survey on Computing', 'Some description. ', 1, '2018-03-16', '2018-03-17', '2018-03-09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `createdDate` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `firstName`, `lastName`, `email`, `password`, `createdDate`) VALUES
(1, 'snowmonkey', 'Tobias', 'Parent', 'parent.tobias@gmail.com', 'fooBarBazFlap', '0000-00-00');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answerOptions`
--
ALTER TABLE `answerOptions`
  ADD CONSTRAINT `answerOptions_ibfk_1` FOREIGN KEY (`questionId`) REFERENCES `questions` (`id`);

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_4` FOREIGN KEY (`answerOptionId`) REFERENCES `answerOptions` (`id`),
  ADD CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`responseId`) REFERENCES `responses` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`surveyId`) REFERENCES `surveys` (`id`),
  ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`questionTypeId`) REFERENCES `questionTypes` (`id`);

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`usersId`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`surveysId`) REFERENCES `surveys` (`id`);

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
