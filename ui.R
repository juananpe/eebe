library(shiny)
library(RMySQL)

setwd("~/Dropbox/Public/articulos/cinaic2015/App-1")

con <- dbConnect( MySQL(), user="juanan", password="", db="stack", host="localhost")
#info <- dbReadTable(con, "info")
# dataf <- dbReadTable(con, "dataf")
res <- dbSendQuery(con, 'SELECT user_id, user_name, max(creputation) as creputation FROM stack.dataf group by user_name')
dataf <- dbFetch(res)
dbClearResult(res)

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
             dateRangeInput("dates", label = h3("Date range"),  start = "2015-01-19")),  
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
