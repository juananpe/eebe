library(shiny)
library(RMySQL)

source("keys.R")

con <- dbConnect( MySQL(), user=login, password=pass, db=database, host=host)

res <- dbSendQuery(con, 'SELECT user_id, user_name, MAX(creputation) AS creputation, fk_group_id
                   FROM dataf s , enrolment e 
                   where s.user_id = e.fk_user_id 
                   GROUP BY user_name')
dataf <- dbFetch(res)
dbClearResult(res)

groupsQuery <- dbSendQuery(con, 'SELECT id, name from groups')
groupsResult <- dbFetch(groupsQuery)
dbClearResult(groupsQuery)

groups <- as.list(setNames(groupsResult$id, groupsResult$name))

lista <- as.list(setNames(dataf$user_id, paste(dataf$user_name, dataf$creputation)))
# lista <- as.list(setNames(info$user_id, info$user_name))
dbDisconnect(con)

# Define UI for application that draws a histogram
shinyUI(fluidPage(
  
  # Application title
  titlePanel("External Evidence Based Evaluation"),
  
  # Sidebar with a slider input for the number of bins
  sidebarLayout(
    fluidRow(
      column(3,
             dateRangeInput("dates", label = h3("Date range"),  start = "2015-01-19"),
             br(),
             selectInput('group_id', 'Group', groups)
             ),
      column(3,
             checkboxGroupInput("users", 
                                label = h3("Badges in SO"), 
                                choices = lista, 
                                selected = dataf$user_id)
    )),
    
    # Show a plot of the generated distribution
    mainPanel(
      plotOutput("distPlot")
    )
  )
))
