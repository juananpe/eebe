library(shiny)
library(RMySQL)

source("keys.R")  
source("functions.R")  
con <- getConnection();
  
groupsQuery <- dbSendQuery(con, 'SELECT id, name from groups')
groupsResult <- dbFetch(groupsQuery)
dbClearResult(groupsQuery)

groups <- as.list(setNames(groupsResult$id, groupsResult$name))

shinyUI(fluidPage(
  
  titlePanel("External Evidence Based Evaluation"),
  
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
                                choices = list(), selected = c())
    )),
    
    mainPanel(
      plotOutput("distPlot")
    )
  )
))