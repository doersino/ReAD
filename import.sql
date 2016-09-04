SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `read` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `url` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `time_added` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `archived` tinyint(1) NOT NULL,
  `starred` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

INSERT INTO `read` (`id`, `url`, `title`, `time_added`, `time`, `archived`, `starred`) VALUES
(1, 'http://www.43folders.com/2011/04/22/cranking', 'Cranking | 43 Folders', 1407667692, 1407667692, 1, 1),
(2, 'http://news.stanford.edu/news/2005/june15/jobs-061505.html', 'Text of Steve Jobs'' Commencement address (2005)', 1407668713, 1407668713, 0, 0),
(3, 'http://www.cgpgrey.com/blog/i-have-died-many-times', 'I Have Died Many Times', 1407668745, 1407668745, 1, 0),
(4, 'http://lifehacker.com/im-ira-glass-host-of-this-american-life-and-this-is-h-1609562031/all', 'I''m Ira Glass, Host of This American Life, and This Is How I Work', 1407668760, 1407668760, 1, 0);

CREATE TABLE IF NOT EXISTS `read_sources` (
  `id` mediumint(9) NOT NULL,
  `source` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `read_sources` (`id`, `source`) VALUES
(1, ''),
(2, ''),
(3, ''),
(4, '');

ALTER TABLE `read_sources`
  ADD CONSTRAINT `read_sources_ibfk_1` FOREIGN KEY (`id`) REFERENCES `read` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
