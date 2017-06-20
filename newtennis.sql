CREATE DATABASE IF NOT EXISTS nt;
USE nt;

DROP TABLE IF EXISTS TENNISMATCH, PLAYER;

/* TRAINER and CLIENT can be wp_users */

CREATE TABLE PLAYER(
	PlayerID int not null auto_increment,
	FirstName varchar(15) not null,
	LastName varchar(15) not null,
	Email varchar(50) not null,
	Cell varchar(20) not null,
	Playertype ENUM('active', 'sub', 'inactive'),
	PRIMARY KEY(PlayerID),
	CHECK (PlayerID > 0)
) engine = InnoDB;

CREATE TABLE TENNISMATCH(
	TennisMatchID    int not null auto_increment,
	MatchDate date,
	MatchTime time,
	Host int,
	Player1 int,
	Player2 int,
	Player3 int,
	HostStatus ENUM('okay', 'needsub'),
	P1Status ENUM('okay', 'needsub')
	P2Status ENUM('okay', 'needsub')
	P3Status ENUM('okay', 'needsub')
	FOREIGN KEY(HOST) references PLAYER(PlayerID),
	FOREIGN KEY(PLAYER1) references PLAYER(PlayerID),
	FOREIGN KEY(PLAYER1) references PLAYER(PlayerID),
	FOREIGN KEY(PLAYER1) references PLAYER(PlayerID),
	PRIMARY KEY(TennisMatchID),
	CHECK (TennisMatchID > 0)
) engine = InnoDB;

/* See today's match */
select * from TENNISMATCH where MatchDate=DATE(NOW());

/* Sign up for a future match */
UPDATE TENNISMATCH set Player1='3' where TennisMatchID='4';

/* See Signup stats */
select *
 from TENNISMATCH join PLAYER on TENNISMATCH.Host = PLAYER.PlayerID
join PLAYER on TENNISMATCH.Player1 = PLAYER.PlayerID
join PLAYER on TENNISMATCH.Player2 = PLAYER.PlayerID
join PLAYER on TENNISMATCH.Player2 = PLAYER.PlayerID;

/* Respond to a message */
/* manaage account */

/* signup */
/* ADMIN Use Cases */
/* invite members */
/* setup matches */
/* Manage Schedule rules */

/* Crontab use cases */
/* send emails */
/* send texts */
/* create matches */
