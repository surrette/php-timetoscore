# php-timetoscore
 Syncs hockey stats from timetoscore scoresheets to MySQL database.

## MySQL database
```
CREATE TABLE `games` (
  `GameId` varchar(45) NOT NULL,
  `HomeTeam` varchar(200) DEFAULT NULL,
  `AwayTeam` varchar(200) DEFAULT NULL,
  `Name` varchar(1000) DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `Surface` varchar(50) DEFAULT NULL,
  `Auth` varchar(45) DEFAULT NULL,
  `FileName` varchar(200) DEFAULT NULL,
  `YouTubeLink` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`GameId`),
  UNIQUE KEY `GameId_UNIQUE` (`GameId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `highlights` (
  `HighlightId` int(11) NOT NULL AUTO_INCREMENT,
  `GameId` int(11) DEFAULT NULL,
  `Team` varchar(200) DEFAULT NULL,
  `Name` varchar(1000) DEFAULT NULL,
  `Type` varchar(45) DEFAULT NULL,
  `SubType` varchar(45) DEFAULT NULL,
  `StartTime` int(11) DEFAULT NULL,
  `EndTime` int(11) DEFAULT NULL,
  `FileName` varchar(200) DEFAULT NULL,
  `YouTubeLink` varchar(200) DEFAULT NULL,
  `Period` int(11) DEFAULT NULL,
  `GameTime` varchar(45) DEFAULT NULL,
  `GoalNum` int(11) DEFAULT NULL,
  `Verified` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`HighlightId`),
  UNIQUE KEY `unique_highlight` (`GameId`,`Team`,`Type`,`SubType`,`Period`,`GameTime`,`GoalNum`)
) ENGINE=InnoDB AUTO_INCREMENT=623 DEFAULT CHARSET=utf8;
```