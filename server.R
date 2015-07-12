library(shiny)
library(ggplot2)
library(RMySQL)
library(plyr)

# setwd("~/Dropbox/Public/articulos/cinaic2015/App-1")
source("functions.R")

# Define server logic required to draw a histogram
shinyServer(function(input, output) {
  
  con <- dbConnect( MySQL(), user="juanan", password="", db="stack", host="localhost")
  info <- dbReadTable(con, "info")
  medallas <- dbReadTable(con, "medallas")
  dataf <- dbReadTable(con, "dataf")
  dataf$creation_date <- as.POSIXct(dataf$creation_date)
  dataf$reputation_change <- as.integer(dataf$reputation_change)
  dataf <- join(dataf, info, by="user_id")
  dbDisconnect(con)
  
output$distPlot <- renderPlot({
    #medallas$orden <- ordered( as.factor(medallas$rank), levels = c("bronze", "silver", "gold"))
    medals <- medallas[medallas$user_user_id %in%  input$users,]
    medals$orden <- ordered( as.factor(medals$rank), levels = c("bronze", "silver", "gold"))
#    medals$usuarios <- input$users
    # user_display_name
    p1 <- ggplot(medals, aes(x=user_display_name, y=howmany, fill=orden, order=orden)) + geom_bar(stat="identity")  + scale_fill_discrete(breaks=c("bronze","silver","gold"))
    p1 + theme(axis.text.x = element_text(angle = 45, hjust = 1))

    reputation <- dataf[dataf$user_id %in%  input$users,]
    
    p2 <- ggplot(reputation[reputation$creation_date > as.POSIXlt( input$dates[1] ) & reputation$creation_date  < as.POSIXlt ( input$dates[2] ),], aes(x=creation_date, y=creputation, group=user_name, colour=user_name)) + geom_line()
    
    multiplot(p1,p2,cols=2)
    
  })
})
