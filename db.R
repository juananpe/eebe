library(jsonlite)
library(RCurl)
library(sqldf)
library(plyr)
library(RMySQL)

source("keys.R")
con <- dbConnect( MySQL(), user=login, password=pass, db=database, host=host)

info <- dbReadTable(con, "info")

users <- info$user_id
users_names <- info$user_name

info <- data.frame(users, users_names)
names(info) <- c("user_id","user_name")

# truncate existing tables
# dbSendQuery(con, 'truncate table info')

dbSendQuery(con, 'truncate table dataf')
dbSendQuery(con, 'truncate table medallas')

# dataf <- dbReadTable(con, "dataf")


i <- 1
has_more <- TRUE

dataf <- data.frame()
while (has_more) {
  datos <- getURL(paste("http://api.stackexchange.com/2.2/users/", paste(users, collapse=";") , "/badges?page=",i,"+&order=desc&sort=rank&site=stackoverflow",sep=""),
                  encoding="gzip")  
  ndf <- fromJSON(datos, flatten=TRUE)
  dataf <- rbind(dataf, ndf$items)
  i <- i+1
  has_more <- ndf$has_more
}

badges <- dataf
names(badges) <- gsub("\\.","_",names(badges))
medallas <- sqldf("select user_user_id, user_display_name, rank, count(*) as howmany from
                  badges group by user_user_id, rank",drv="SQLite")


# reputation
direccion <- paste("http://api.stackexchange.com/2.2/users/" , paste(users,collapse=";"), "/reputation-history?site=stackoverflow", collapse="", sep="")
i <- 1
has_more <- TRUE
dataf <- data.frame()
while (has_more) {
  datos <- getURL(paste(direccion, "&page=",i, sep=""), encoding="gzip") 
  ndf <- fromJSON(datos, flatten=TRUE)
  dataf <- rbind(dataf, ndf$items)
  i <- i+1
  has_more <- ndf$has_more
}
dataf$creation_date <- as.POSIXct( dataf$creation_date , origin="1970-01-01")
dataf$user_id <- as.factor(dataf$user_id)
# invert
dataf <- dataf[nrow(dataf):1,]
dataf <- ddply(dataf, .(user_id), transform, creputation = cumsum(reputation_change))

# add user names
dataf <- join(dataf, info, by="user_id")

# df <- data2$items

dbWriteTable(con, "dataf", dataf, overwrite=TRUE)
dbWriteTable(con, "info", info, overwrite=TRUE)
dbWriteTable(con, "medallas", medallas, overwrite=TRUE)

dbDisconnect(con)

#> sapply(dataf, class)
#$reputation_history_type
#[1] "character"
#
#$reputation_change
#[1] "integer"
#
#$post_id
#[1] "integer"
#
#$creation_date
#[1] "POSIXct" "POSIXt" 
#
#$user_id
#[1] "factor"
#
#$creputation
#[1] "integer"
