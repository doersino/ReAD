SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `read` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `url` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `source` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  `archived` tinyint(1) NOT NULL,
  `starred` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

INSERT INTO `read` (`id`, `url`, `source`, `title`, `time`, `archived`, `starred`) VALUES
(1, 'http://www.43folders.com/2011/04/22/cranking', 'Source code of sample articles not included.', 'Cranking | 43 Folders', 1407667692, 1, 1),
(2, 'http://news.stanford.edu/news/2005/june15/jobs-061505.html', 'Source code of sample articles not included.', 'Text of Steve Jobs'' Commencement address (2005)', 1407668713, 0, 0),
(3, 'http://www.cgpgrey.com/blog/i-have-died-many-times', 'Source code of sample articles not included.', 'I Have Died Many Times â€” CGP Grey', 1407668745, 1, 0),
(4, 'http://lifehacker.com/im-ira-glass-host-of-this-american-life-and-this-is-h-1609562031/all', 'Source code of sample articles not included.', 'I''m Ira Glass, Host of This American Life, and This Is How I Work', 1407668760, 1, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
