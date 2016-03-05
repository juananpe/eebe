library(shiny)
library(ggplot2)
library(RMySQL)
library(plyr)

source("keys.R")  
source("functions.R")

shinyServer(function(input, output, session) {
  con <- getConnection();
  info <- dbReadTable(con, "info")
  medallas <- dbReadTable(con, "medallas")
  dataf <- dbReadTable(con, "dataf")
  dataf$creation_date <- as.POSIXct(dataf$creation_date)
  dataf$reputation_change <- as.integer(dataf$reputation_change)
  dataf <- join(dataf, info, by="user_id")
  
  groupRes <- dbSendQuery(con, paste('SELECT user_id, user_name, MAX(creputation) AS creputation, fk_group_id
                FROM stack.dataf s , enrolment e 
                      where s.user_id = e.fk_user_id GROUP BY user_name'))
  dataGroup <- dbFetch(groupRes)
  dbClearResult(groupRes)
   
  listaGroup <- as.list(setNames(dataGroup$user_id, paste(dataGroup$user_name, dataGroup$creputation)))
  
  observe({
      groupId <- parseQueryString(session$clientData$url_search)
      if ( length(groupId) > 0) {
        print(paste("groupId:",groupId))
      } else {
        groupId = 1
      }
    res <- dbSendQuery(con, paste('SELECT user_id, user_name, MAX(creputation) AS creputation, fk_group_id
                      FROM dataf s , enrolment e 
                      where s.user_id = e.fk_user_id and e.fk_group_id = ', groupId , '
                      GROUP BY user_name'))
      
      
      dataf <- dbFetch(res)
      lista <- as.list(setNames(dataf$user_id, dataf$user_name))

      updateCheckboxGroupInput(session = session, inputId = "users", 
                               choices = lista, selected = dataf$user_id ) 
  })
  
output$distPlot <- renderPlot({
    medals <- medallas[medallas$user_user_id %in%  input$users,]
    medals$orden <- ordered( as.factor(medals$rank), levels = c("bronze", "silver", "gold"))
    p1 <- ggplot(medals, aes(x=user_display_name, y=howmany, fill=orden, order=orden)) + geom_bar(stat="identity")  + scale_fill_discrete(breaks=c("bronze","silver","gold"))
    p1 + theme(axis.text.x = element_text(angle = 45, hjust = 1))

    reputation <- dataf[dataf$user_id %in%  input$users,]
    
    p2 <- ggplot(reputation[reputation$creation_date > as.POSIXlt( input$dates[1] ) & reputation$creation_date  < as.POSIXlt ( input$dates[2] ),], aes(x=creation_date, y=creputation, group=user_name, colour=user_name)) + geom_line()
    
    multiplot(p1,p2,cols=2)
    
  })

})