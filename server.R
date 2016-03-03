library(shiny)
library(ggplot2)
library(RMySQL)
library(plyr)

source("functions.R")

# Define server logic required to draw a histogram
shinyServer(function(input, output, session) {
  source("keys.R")  
  con <- dbConnect( MySQL(), user=login, password=pass, db=database, host=host)
  info <- dbReadTable(con, "info")
  medallas <- dbReadTable(con, "medallas")
  dataf <- dbReadTable(con, "dataf")
  dataf$creation_date <- as.POSIXct(dataf$creation_date)
  dataf$reputation_change <- as.integer(dataf$reputation_change)
  dataf <- join(dataf, info, by="user_id")
  dbDisconnect(con)
  
  observe({
  #  query <- parseQueryString(session$clientData$url_search)
  #  if (!is.null(query[['group']])) {
    
  
      # updateSelectInput(session, "group_id", label = "Grupos", choices = c("All"=0, "Juanan"=1,"Mikel"=10), selected =  input$group_id)
      # 
      # res <- dbSendQuery(con, paste('SELECT user_id, user_name, MAX(creputation) AS creputation, fk_group_id
      #              FROM stack.dataf s , enrolment e 
      #                    where s.user_id = e.fk_user_id and fk_group_id = ', input$group_id ,' GROUP BY user_name;'))
      # dataf <- dbFetch(res)
      # dbClearResult(res)
      # 
      # lista <- as.list(setNames(dataf$user_id, paste(dataf$user_name, dataf$creputation)))
      # 
      # updateCheckboxGroupInput(session, "users", 
      #                    label = h3("Badges in SO"), 
      #                    choices = lista, 
      #                    selected = dataf$user_id)
   # }
  })
  
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
