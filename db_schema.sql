--
-- Database: `adc_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `users_offline`
--

CREATE TABLE IF NOT EXISTS `users_offline` (
  `user_id` int(11) NOT NULL,
  `dtStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_online`
--

CREATE TABLE IF NOT EXISTS `users_online` (
  `user_id` int(11) NOT NULL,
  `dtStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;